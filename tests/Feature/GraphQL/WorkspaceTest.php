<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMembership;
use App\Modules\Workspaces\Notifications\WorkspaceInvitationNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

test('verified users create workspaces with an owner', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/graphql', [
        'query' => 'mutation { createWorkspace(input: { name: "  Example  " }) { id name } }',
    ])->assertJsonPath('data.createWorkspace.name', 'Example');

    $workspace = Workspace::query()->where('uuid', $response->json('data.createWorkspace.id'))->firstOrFail();
    $this->assertDatabaseHas('workspace_memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Owner->value,
        'deleted_at' => null,
    ]);
});

test('workspace creation rejects guests and unverified users', function () {
    $query = 'mutation { createWorkspace(input: { name: "Example" }) { id } }';
    $this->postJson('/graphql', ['query' => $query])->assertJsonPath('errors.0.extensions.code', 'UNAUTHENTICATED');
    $this->actingAs(User::factory()->unverified()->create())
        ->postJson('/graphql', ['query' => $query])->assertJsonPath('errors.0.extensions.code', 'FORBIDDEN');
    expect(Workspace::query()->count())->toBe(0);
});

test('workspace creation rejects empty names', function (string $name) {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/graphql', [
        'query' => 'mutation ($name: String!) { createWorkspace(input: { name: $name }) { id name } }',
        'variables' => ['name' => $name],
    ])->assertJsonPath('errors.0.message', 'Variable "$name" of non-null type "String!" must not be null.');

    expect(Workspace::query()->count())->toBe(0)
        ->and(WorkspaceMembership::query()->count())->toBe(0);
})->with([
    'empty' => '',
    'spaces' => '     ',
]);

test('workspace creation rejects names longer than 100 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/graphql', [
        'query' => 'mutation ($name: String!) { createWorkspace(input: { name: $name }) { id name } }',
        'variables' => ['name' => str_repeat('ы', 101)],
    ])->assertJsonStructure([
        'errors' => [
            ['extensions' => ['validation' => ['name']]],
        ],
    ]);

    expect(Workspace::query()->count())->toBe(0)
        ->and(WorkspaceMembership::query()->count())->toBe(0);
});

test('workspace query only shows workspaces of the current user', function () {
    $user = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['user_id' => $user->id]);
    $other = Workspace::factory()->create();
    $query = 'query ($id: ID!) { workspace(id: $id) { id name } }';

    $this->actingAs($user)->postJson('/graphql', ['query' => $query, 'variables' => ['id' => $membership->workspace->uuid]])
        ->assertJsonPath('data.workspace.id', $membership->workspace->uuid);
    $this->postJson('/graphql', ['query' => $query, 'variables' => ['id' => $other->uuid]])
        ->assertJsonPath('data.workspace', null);
});

test('workspace list only includes active memberships', function () {
    $user = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['user_id' => $user->id]);
    WorkspaceMembership::factory()->create();
    $removed = WorkspaceMembership::factory()->create(['user_id' => $user->id]);
    $removed->delete();

    $this->actingAs($user)->postJson('/graphql', ['query' => '{ myWorkspaces(first: 1) { edges { node { id } } } }'])
        ->assertJsonPath('data.myWorkspaces.edges.0.node.id', $membership->workspace->uuid)
        ->assertJsonCount(1, 'data.myWorkspaces.edges');
});

test('workspace name can be changed by owner and admin', function (WorkspaceRole $role) {
    $user = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['user_id' => $user->id, 'role' => $role]);
    $query = 'mutation ($input: UpdateWorkspaceInput!) { updateWorkspace(input: $input) { id name } }';
    $this->actingAs($user)->postJson('/graphql', ['query' => $query, 'variables' => ['input' => ['id' => $membership->workspace->uuid, 'name' => ' New name ']]])
        ->assertJsonPath('data.updateWorkspace.name', 'New name');
})->with([WorkspaceRole::Owner, WorkspaceRole::Admin]);

test('workspace name cannot be changed by developer or viewer', function (WorkspaceRole $role) {
    $user = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['user_id' => $user->id, 'role' => $role]);
    $query = 'mutation ($input: UpdateWorkspaceInput!) { updateWorkspace(input: $input) { id } }';
    $this->actingAs($user)->postJson('/graphql', ['query' => $query, 'variables' => ['input' => ['id' => $membership->workspace->uuid, 'name' => 'New name']]])
        ->assertJsonPath('errors.0.extensions.code', 'FORBIDDEN');
    expect($membership->workspace->fresh()->name)->not->toBe('New name');
})->with([WorkspaceRole::Developer, WorkspaceRole::Viewer]);

test('only the owner can delete and restore a workspace', function (WorkspaceRole $role) {
    $user = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['user_id' => $user->id, 'role' => $role]);
    $id = $membership->workspace->uuid;
    $query = 'mutation ($input: WorkspaceIdInput!) { deleteWorkspace(input: $input) }';
    $response = $this->actingAs($user)->postJson('/graphql', ['query' => $query, 'variables' => ['input' => ['id' => $id]]]);
    if ($role === WorkspaceRole::Owner) {
        $response->assertJsonPath('data.deleteWorkspace', true);
        $this->assertSoftDeleted('workspaces', ['id' => $membership->workspace_id]);
        $this->postJson('/graphql', ['query' => 'mutation ($input: WorkspaceIdInput!) { restoreWorkspace(input: $input) }', 'variables' => ['input' => ['id' => $id]]])->assertJsonPath('data.restoreWorkspace', true);
        $this->assertNotSoftDeleted('workspaces', ['id' => $membership->workspace_id]);
    } else {
        $response->assertJsonPath('errors.0.extensions.code', 'FORBIDDEN');
        $this->assertNotSoftDeleted('workspaces', ['id' => $membership->workspace_id]);
    }
})->with([WorkspaceRole::Owner, WorkspaceRole::Admin, WorkspaceRole::Developer, WorkspaceRole::Viewer]);

test('owner transfers ownership to a verified member', function () {
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $old = WorkspaceMembership::factory()->create(['user_id' => $owner->id, 'workspace_id' => $workspace->id, 'role' => WorkspaceRole::Owner]);
    $member = WorkspaceMembership::factory()->create(['workspace_id' => $workspace->id, 'role' => WorkspaceRole::Viewer]);
    $this->actingAs($owner)->postJson('/graphql', [
        'query' => 'mutation ($input: WorkspaceMemberInput!) { transferWorkspaceOwnership(input: $input) }',
        'variables' => ['input' => ['id' => $workspace->uuid, 'memberId' => $member->uuid]],
    ])->assertJsonPath('data.transferWorkspaceOwnership', true);
    expect($old->fresh()->role)->toBe(WorkspaceRole::Admin)->and($member->fresh()->role)->toBe(WorkspaceRole::Owner);
});

test('owner cannot leave without transferring ownership', function () {
    $owner = User::factory()->create();
    $member = WorkspaceMembership::factory()->create(['user_id' => $owner->id, 'role' => WorkspaceRole::Owner]);
    $this->actingAs($owner)->postJson('/graphql', [
        'query' => 'mutation ($input: WorkspaceIdInput!) { leaveWorkspace(input: $input) }',
        'variables' => ['input' => ['id' => $member->workspace->uuid]],
    ])->assertJsonStructure(['errors']);
    $this->assertNotSoftDeleted('workspace_memberships', ['id' => $member->id]);
});

test('members are listed only inside a visible workspace', function () {
    $user = User::factory()->create();
    $member = WorkspaceMembership::factory()->create(['user_id' => $user->id, 'role' => WorkspaceRole::Viewer]);
    $other = Workspace::factory()->create();
    $query = 'query ($id: ID!) { workspaceMembers(id: $id, first: 1) { edges { node { id role user { name } } } } }';
    $this->actingAs($user)->postJson('/graphql', ['query' => $query, 'variables' => ['id' => $member->workspace->uuid]])
        ->assertJsonPath('data.workspaceMembers.edges.0.node.id', $member->uuid)
        ->assertJsonPath('data.workspaceMembers.edges.0.node.role', 'viewer');
    $this->postJson('/graphql', ['query' => $query, 'variables' => ['id' => $other->uuid]])
        ->assertJsonCount(0, 'data.workspaceMembers.edges');
});

test('invitations can be accepted once by the invited user', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['user_id' => $owner->id, 'role' => WorkspaceRole::Owner]);
    $this->actingAs($owner)->postJson('/graphql', [
        'query' => 'mutation ($input: InviteWorkspaceMemberInput!) { inviteWorkspaceMember(input: $input) { id email } }',
        'variables' => ['input' => ['id' => $membership->workspace->uuid, 'email' => $invitee->email, 'role' => 'DEVELOPER']],
    ])->assertJsonPath('data.inviteWorkspaceMember.email', $invitee->email);
    $notification = Notification::sent(new AnonymousNotifiable, WorkspaceInvitationNotification::class)->first();
    expect($notification)->not->toBeNull();
    $mail = $notification->toMail(new AnonymousNotifiable);
    parse_str((string) parse_url($mail->actionUrl, PHP_URL_FRAGMENT), $parts);
    $query = 'mutation ($token: String!) { acceptWorkspaceInvitation(token: $token) }';
    $this->actingAs($invitee)->postJson('/graphql', ['query' => $query, 'variables' => ['token' => $parts['token']]])
        ->assertJsonPath('data.acceptWorkspaceInvitation', true);
    $this->postJson('/graphql', ['query' => $query, 'variables' => ['token' => $parts['token']]])->assertJsonStructure(['errors']);
    $this->assertDatabaseHas('workspace_memberships', ['workspace_id' => $membership->workspace_id, 'user_id' => $invitee->id, 'role' => 'developer']);
});

test('workspace member roles can be managed only by owner or admin', function (WorkspaceRole $role, bool $allowed) {
    $actor = User::factory()->create();
    $workspace = Workspace::factory()->create();
    WorkspaceMembership::factory()->create(['user_id' => $actor->id, 'workspace_id' => $workspace->id, 'role' => $role]);
    $member = WorkspaceMembership::factory()->create(['workspace_id' => $workspace->id, 'role' => WorkspaceRole::Viewer]);
    $response = $this->actingAs($actor)->postJson('/graphql', [
        'query' => 'mutation ($input: ChangeWorkspaceMemberRoleInput!) { changeWorkspaceMemberRole(input: $input) }',
        'variables' => ['input' => ['id' => $workspace->uuid, 'memberId' => $member->uuid, 'role' => 'DEVELOPER']],
    ]);
    if ($allowed) {
        $response->assertJsonPath('data.changeWorkspaceMemberRole', true);
        expect($member->fresh()->role)->toBe(WorkspaceRole::Developer);
    } else {
        $response->assertJsonPath('errors.0.extensions.code', 'FORBIDDEN');
        expect($member->fresh()->role)->toBe(WorkspaceRole::Viewer);
    }
})->with([[WorkspaceRole::Owner, true], [WorkspaceRole::Admin, true], [WorkspaceRole::Developer, false], [WorkspaceRole::Viewer, false]]);

test('invitations reject a different email, expiry and revocation', function (string $state) {
    $user = User::factory()->create();
    $invitation = WorkspaceInvitation::factory()->create([
        'email' => $state === 'email' ? 'other@example.test' : $user->email,
        'token_hash' => hash('sha256', 'token'),
        'expires_at' => $state === 'expired' ? now()->subDay() : now()->addDay(),
        'revoked_at' => $state === 'revoked' ? now() : null,
    ]);
    $this->actingAs($user)->postJson('/graphql', [
        'query' => 'mutation ($token: String!) { acceptWorkspaceInvitation(token: $token) }',
        'variables' => ['token' => 'token'],
    ])->assertJsonStructure(['errors']);
    $this->assertDatabaseMissing('workspace_memberships', ['workspace_id' => $invitation->workspace_id, 'user_id' => $user->id]);
})->with(['email', 'expired', 'revoked']);

test('an admin can revoke an invitation', function () {
    $admin = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['user_id' => $admin->id, 'role' => WorkspaceRole::Admin]);
    $invitation = WorkspaceInvitation::factory()->create(['workspace_id' => $membership->workspace_id]);
    $this->actingAs($admin)->postJson('/graphql', [
        'query' => 'mutation ($input: RevokeWorkspaceInvitationInput!) { revokeWorkspaceInvitation(input: $input) }',
        'variables' => ['input' => ['id' => $membership->workspace->uuid, 'invitationId' => $invitation->uuid]],
    ])->assertJsonPath('data.revokeWorkspaceInvitation', true);
    expect($invitation->fresh()->revoked_at)->not->toBeNull();
});

test('invitation list is limited to owners and admins', function (WorkspaceRole $role, int $count) {
    $user = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['user_id' => $user->id, 'role' => $role]);
    WorkspaceInvitation::factory()->create(['workspace_id' => $membership->workspace_id]);
    $this->actingAs($user)->postJson('/graphql', [
        'query' => 'query ($id: ID!) { workspaceInvitations(id: $id, first: 1) { edges { node { id } } } }',
        'variables' => ['id' => $membership->workspace->uuid],
    ])->assertJsonCount($count, 'data.workspaceInvitations.edges');
})->with([[WorkspaceRole::Owner, 1], [WorkspaceRole::Admin, 1], [WorkspaceRole::Developer, 0], [WorkspaceRole::Viewer, 0]]);

test('a member from another workspace cannot be removed', function () {
    $owner = User::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['user_id' => $owner->id, 'role' => WorkspaceRole::Owner]);
    $other = WorkspaceMembership::factory()->create();
    $this->actingAs($owner)->postJson('/graphql', [
        'query' => 'mutation ($input: WorkspaceMemberInput!) { removeWorkspaceMember(input: $input) }',
        'variables' => ['input' => ['id' => $membership->workspace->uuid, 'memberId' => $other->uuid]],
    ])->assertJsonStructure(['errors']);
    $this->assertNotSoftDeleted('workspace_memberships', ['id' => $other->id]);
});

test('workspace list advances with a cursor and validates page size', function () {
    $user = User::factory()->create();
    $first = WorkspaceMembership::factory()->create(['user_id' => $user->id]);
    $second = WorkspaceMembership::factory()->create(['user_id' => $user->id]);
    $query = 'query ($after: String) { myWorkspaces(first: 1, after: $after) { edges { node { id } } pageInfo { hasNextPage endCursor } } }';
    $response = $this->actingAs($user)->postJson('/graphql', ['query' => $query]);
    $response->assertJsonPath('data.myWorkspaces.pageInfo.hasNextPage', true);
    $one = $response->json('data.myWorkspaces.edges.0.node.id');
    $cursor = $response->json('data.myWorkspaces.pageInfo.endCursor');
    $next = $this->postJson('/graphql', ['query' => $query, 'variables' => ['after' => $cursor]]);
    $next->assertJsonPath('data.myWorkspaces.pageInfo.hasNextPage', false);
    expect([$one, $next->json('data.myWorkspaces.edges.0.node.id')])->toContain($first->workspace->uuid, $second->workspace->uuid);
    $this->postJson('/graphql', ['query' => '{ myWorkspaces(first: 101) { edges { cursor } } }'])->assertJsonStructure(['errors']);
    $this->postJson('/graphql', ['query' => $query, 'variables' => ['after' => 'invalid']])->assertJsonStructure(['errors']);
});
