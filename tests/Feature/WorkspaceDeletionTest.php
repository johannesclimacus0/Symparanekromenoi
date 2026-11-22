<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;

test('account deletion preserves users and memberships as deleted records', function () {
    config(['sanctum.stateful' => ['localhost']]);
    $user = User::factory()->create(['password' => 'password']);
    $workspace = Workspace::factory()->create();
    $membership = WorkspaceMembership::factory()->create(['workspace_id' => $workspace->id, 'user_id' => $user->id]);

    $this->withHeader('Origin', 'http://localhost')->actingAs($user)->postJson('/graphql', [
        'query' => 'mutation { deleteMyAccount(input: { password: "password" }) }',
    ])->assertJsonPath('data.deleteMyAccount', true);

    $this->assertSoftDeleted('users', ['id' => $user->id]);
    $this->assertSoftDeleted('workspace_memberships', ['id' => $membership->id]);
    expect($workspace->users()->count())->toBe(0);
});

test('owners cannot delete their accounts', function () {
    config(['sanctum.stateful' => ['localhost']]);
    $user = User::factory()->create(['password' => 'password']);
    $membership = WorkspaceMembership::factory()->create(['user_id' => $user->id, 'role' => WorkspaceRole::Owner]);

    $this->withHeader('Origin', 'http://localhost')->actingAs($user)->postJson('/graphql', [
        'query' => 'mutation { deleteMyAccount(input: { password: "password" }) }',
    ])->assertJsonStructure(['errors']);

    $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    $this->assertNotSoftDeleted('workspace_memberships', ['id' => $membership->id]);
    $this->assertAuthenticatedAs($user);
});
