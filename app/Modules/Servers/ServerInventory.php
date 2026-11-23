<?php

declare(strict_types=1);

namespace App\Modules\Servers;

use App\Enums\WorkspaceRole;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ServerInventory
{
    /** @return Builder<Server> */
    public function visible(User $user): Builder
    {
        return Server::query()->whereIn('workspace_id', $user->workspaces()->select('workspaces.id'));
    }

    public function find(User $user, string $uuid): ?Server
    {
        if (! Str::isUuid($uuid)) {
            return null;
        }

        return $this->visible($user)->where('uuid', $uuid)->first();
    }

    /** @param array{name: string, address: string, description?: string|null} $input */
    public function create(User $user, string $workspaceUuid, array $input): Server
    {
        return DB::transaction(function () use ($user, $workspaceUuid, $input): Server {
            $workspace = $this->authorizedWorkspace($user, $workspaceUuid);

            return Server::create([...$input, 'workspace_id' => $workspace->getKey()]);
        });
    }

    /** @param array{name: string, address: string, description?: string|null} $input */
    public function update(User $user, string $uuid, array $input): Server
    {
        return DB::transaction(function () use ($user, $uuid, $input): Server {
            $server = $this->find($user, $uuid) ?? throw (new ModelNotFoundException)->setModel(Server::class);
            $workspace = $server->workspace()->firstOrFail();
            $this->authorizedWorkspace($user, $workspace->uuid);
            $server = $this->visible($user)->whereKey($server->getKey())->lockForUpdate()->firstOrFail();
            $server->update($input);

            return $server;
        });
    }

    public function delete(User $user, string $uuid): bool
    {
        return DB::transaction(function () use ($user, $uuid): bool {
            $server = $this->find($user, $uuid) ?? throw (new ModelNotFoundException)->setModel(Server::class);
            $workspace = $server->workspace()->firstOrFail();
            $this->authorizedWorkspace($user, $workspace->uuid);
            $server = $this->visible($user)->whereKey($server->getKey())->lockForUpdate()->firstOrFail();
            $server->delete();

            return true;
        });
    }

    private function authorizedWorkspace(User $user, string $uuid): Workspace
    {
        if (! Str::isUuid($uuid)) {
            throw (new ModelNotFoundException)->setModel(Workspace::class);
        }
        $workspace = Workspace::query()->where('uuid', $uuid)->whereIn('id', $user->workspaces()->select('workspaces.id'))->lockForUpdate()->firstOrFail();
        $member = $workspace->memberships()->where('user_id', $user->getKey())->firstOrFail();
        if (! in_array($member->role, [WorkspaceRole::Owner, WorkspaceRole::Admin], true)) {
            throw new AuthorizationException;
        }

        return $workspace;
    }
}
