<?php

declare(strict_types=1);

namespace App\Modules\Workspaces\Actions;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UpdateWorkspace
{
    public function handle(User $user, string $uuid, string $name): Workspace
    {
        if (! Str::isUuid($uuid)) {
            throw (new ModelNotFoundException)->setModel(Workspace::class);
        }

        return DB::transaction(function () use ($user, $uuid, $name): Workspace {
            $workspace = Workspace::query()->where('uuid', $uuid)->whereHas('memberships', fn ($query) => $query->where('user_id', $user->getKey()))->lockForUpdate()->firstOrFail();
            $membership = $workspace->memberships()->where('user_id', $user->getKey())->firstOrFail();
            if (! in_array($membership->role, [WorkspaceRole::Owner, WorkspaceRole::Admin], true)) {
                throw new AuthorizationException;
            }
            $workspace->update(['name' => $name]);

            return $workspace;
        });
    }
}
