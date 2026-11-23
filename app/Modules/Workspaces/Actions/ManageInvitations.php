<?php

declare(strict_types=1);

namespace App\Modules\Workspaces\Actions;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Modules\Workspaces\Notifications\WorkspaceInvitationNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManageInvitations
{
    public function create(User $user, string $workspaceUuid, string $email, WorkspaceRole $role): WorkspaceInvitation
    {
        if (! Str::isUuid($workspaceUuid)) {
            throw (new ModelNotFoundException)->setModel(Workspace::class);
        }
        $token = bin2hex(random_bytes(32));
        $invitation = DB::transaction(function () use ($user, $workspaceUuid, $email, $role, $token): WorkspaceInvitation {
            $workspace = Workspace::query()->where('uuid', $workspaceUuid)->whereHas('memberships', fn ($query) => $query->where('user_id', $user->getKey()))->lockForUpdate()->firstOrFail();
            $member = $workspace->memberships()->where('user_id', $user->getKey())->firstOrFail();
            if (! in_array($member->role, [WorkspaceRole::Owner, WorkspaceRole::Admin], true)) {
                throw new AuthorizationException;
            }
            if ($role === WorkspaceRole::Owner) {
                throw ValidationException::withMessages(['role' => 'An owner cannot be invited.']);
            }

            return WorkspaceInvitation::create([
                'workspace_id' => $workspace->id,
                'invited_by_id' => $user->getKey(),
                'email' => mb_strtolower(trim($email)),
                'role' => $role,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
            ]);
        });
        $workspace = $invitation->workspace()->firstOrFail();
        Notification::route('mail', $invitation->email)->notify(new WorkspaceInvitationNotification($token, $workspace->name));

        return $invitation;
    }

    public function accept(User $user, string $token): bool
    {
        return DB::transaction(function () use ($user, $token): bool {
            $invitation = WorkspaceInvitation::query()->where('token_hash', hash('sha256', $token))->first();
            if (! $invitation) {
                throw ValidationException::withMessages(['token' => 'The invitation is invalid or expired.']);
            }
            $user = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $workspace = Workspace::query()->whereKey($invitation->workspace_id)->lockForUpdate()->first();
            $invitation = WorkspaceInvitation::query()->whereKey($invitation->getKey())->lockForUpdate()->firstOrFail();
            if (! $workspace || $invitation->accepted_at || $invitation->revoked_at || $invitation->expires_at->isPast() || mb_strtolower($user->email) !== $invitation->email || $workspace->memberships()->where('user_id', $user->getKey())->exists()) {
                throw ValidationException::withMessages(['token' => 'The invitation is invalid or expired.']);
            }
            $workspace->memberships()->create(['user_id' => $user->getKey(), 'role' => $invitation->role]);
            $invitation->update(['accepted_at' => now()]);

            return true;
        });
    }

    public function revoke(User $user, string $workspaceUuid, string $invitationUuid): bool
    {
        if (! Str::isUuid($workspaceUuid) || ! Str::isUuid($invitationUuid)) {
            throw (new ModelNotFoundException)->setModel(WorkspaceInvitation::class);
        }

        return DB::transaction(function () use ($user, $workspaceUuid, $invitationUuid): bool {
            $workspace = Workspace::query()->where('uuid', $workspaceUuid)->whereHas('memberships', fn ($query) => $query->where('user_id', $user->getKey()))->lockForUpdate()->firstOrFail();
            $member = $workspace->memberships()->where('user_id', $user->getKey())->firstOrFail();
            if (! in_array($member->role, [WorkspaceRole::Owner, WorkspaceRole::Admin], true)) {
                throw new AuthorizationException;
            }
            $invitation = WorkspaceInvitation::query()->where('workspace_id', $workspace->id)->where('uuid', $invitationUuid)->lockForUpdate()->firstOrFail();
            if ($invitation->accepted_at || $invitation->revoked_at) {
                throw ValidationException::withMessages(['invitation' => 'The invitation is no longer active.']);
            }
            $invitation->update(['revoked_at' => now()]);

            return true;
        });
    }
}
