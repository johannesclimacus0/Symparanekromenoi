<?php

declare(strict_types=1);

namespace App\Modules\Workspaces\Actions;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManageWorkspace
{
    public function delete(User $user, string $uuid): bool
    {
        return DB::transaction(function () use ($user, $uuid): bool {
            $workspace = $this->workspace($user, $uuid);
            $this->authorize($workspace, $user, [WorkspaceRole::Owner]);
            $workspace->delete();

            return true;
        });
    }

    public function restore(User $user, string $uuid): bool
    {
        return DB::transaction(function () use ($user, $uuid): bool {
            $workspace = $this->workspace($user, $uuid, true);
            $this->authorize($workspace, $user, [WorkspaceRole::Owner]);
            $workspace->restore();

            return true;
        });
    }

    public function changeRole(User $user, string $uuid, string $membershipUuid, WorkspaceRole $role): bool
    {
        return DB::transaction(function () use ($user, $uuid, $membershipUuid, $role): bool {
            $workspace = $this->workspace($user, $uuid);
            $this->authorize($workspace, $user, [WorkspaceRole::Owner, WorkspaceRole::Admin]);
            $membership = $this->membership($workspace, $membershipUuid);
            if ($membership->role === WorkspaceRole::Owner || $role === WorkspaceRole::Owner) {
                throw ValidationException::withMessages(['role' => 'Use ownership transfer to change the owner.']);
            }
            $membership->update(['role' => $role]);

            return true;
        });
    }

    public function removeMember(User $user, string $uuid, string $membershipUuid): bool
    {
        return DB::transaction(function () use ($user, $uuid, $membershipUuid): bool {
            $workspace = $this->workspace($user, $uuid);
            $this->authorize($workspace, $user, [WorkspaceRole::Owner, WorkspaceRole::Admin]);
            $membership = $this->membership($workspace, $membershipUuid);
            if ($membership->role === WorkspaceRole::Owner) {
                throw ValidationException::withMessages(['member' => 'Transfer ownership before removing the owner.']);
            }
            $membership->delete();

            return true;
        });
    }

    public function leave(User $user, string $uuid): bool
    {
        return DB::transaction(function () use ($user, $uuid): bool {
            $workspace = $this->workspace($user, $uuid);
            $membership = $workspace->memberships()->where('user_id', $user->getKey())->firstOrFail();
            if ($membership->role === WorkspaceRole::Owner) {
                throw ValidationException::withMessages(['member' => 'Transfer ownership before leaving.']);
            }
            $membership->delete();

            return true;
        });
    }

    public function transfer(User $user, string $uuid, string $membershipUuid): bool
    {
        return DB::transaction(function () use ($user, $uuid, $membershipUuid): bool {
            if (! Str::isUuid($uuid) || ! Str::isUuid($membershipUuid)) {
                throw (new ModelNotFoundException)->setModel(WorkspaceMembership::class);
            }
            $target = WorkspaceMembership::query()
                ->where('uuid', $membershipUuid)
                ->whereHas('workspace', fn ($query) => $query->where('uuid', $uuid)
                    ->whereHas('memberships', fn ($members) => $members->where('user_id', $user->getKey())))
                ->firstOrFail();
            $targetUser = User::query()->whereKey($target->user_id)->lockForUpdate()->firstOrFail();
            $workspace = $this->workspace($user, $uuid);
            $owner = $this->authorize($workspace, $user, [WorkspaceRole::Owner]);
            $member = $this->membership($workspace, $membershipUuid);
            if ($owner->is($member) || ! $targetUser->hasVerifiedEmail()) {
                throw ValidationException::withMessages(['member' => 'Select another member with a verified email address.']);
            }
            $owner->update(['role' => WorkspaceRole::Admin]);
            $member->update(['role' => WorkspaceRole::Owner]);

            return true;
        });
    }

    private function workspace(User $user, string $uuid, bool $trashed = false): Workspace
    {
        if (! Str::isUuid($uuid)) {
            throw (new ModelNotFoundException)->setModel(Workspace::class);
        }
        $query = Workspace::query();
        if ($trashed) {
            $query->onlyTrashed();
        }

        return $query->where('uuid', $uuid)->whereHas('memberships', fn ($query) => $query->where('user_id', $user->getKey()))->lockForUpdate()->firstOrFail();
    }

    /** @param list<WorkspaceRole> $roles */
    private function authorize(Workspace $workspace, User $user, array $roles): WorkspaceMembership
    {
        $membership = $workspace->memberships()->where('user_id', $user->getKey())->firstOrFail();
        if (! in_array($membership->role, $roles, true)) {
            throw new AuthorizationException;
        }

        return $membership;
    }

    private function membership(Workspace $workspace, string $uuid): WorkspaceMembership
    {
        if (! Str::isUuid($uuid)) {
            throw (new ModelNotFoundException)->setModel(WorkspaceMembership::class);
        }

        return $workspace->memberships()->where('uuid', $uuid)->firstOrFail();
    }
}
