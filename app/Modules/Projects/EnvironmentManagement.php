<?php

declare(strict_types=1);

namespace App\Modules\Projects;

use App\Models\Environment;
use App\Models\Project;
use App\Models\User;
use App\Modules\Servers\ServerInventory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class EnvironmentManagement
{
    public function __construct(private ProjectManagement $projects, private ServerInventory $servers) {}

    /** @return Builder<Environment> */
    public function visible(User $user): Builder
    {
        return Environment::query()->whereHas('project', fn ($query) => $query->whereIn('workspace_id', $user->workspaces()->select('workspaces.id')));
    }

    public function find(User $user, string $uuid): ?Environment
    {
        return Str::isUuid($uuid) ? $this->visible($user)->where('uuid', $uuid)->first() : null;
    }

    /** @param array{name: string, serverId?: string|null, description?: string|null} $input */
    public function create(User $user, string $projectUuid, array $input): Environment
    {
        return DB::transaction(function () use ($user, $projectUuid, $input): Environment {
            $project = $this->projects->find($user, $projectUuid) ?? throw (new ModelNotFoundException)->setModel(Project::class);
            $workspace = $this->projects->workspace($user, $project->workspace()->firstOrFail()->uuid);
            $project = $this->projects->visible($user)->whereKey($project->getKey())->lockForUpdate()->firstOrFail();
            $serverId = $this->serverId($user, $input['serverId'] ?? null, $workspace->getKey());
            $this->validateName($project, $input['name']);

            return Environment::create(['project_id' => $project->getKey(), 'server_id' => $serverId, 'name' => $input['name'], 'description' => $input['description'] ?? null]);
        });
    }

    /** @param array{name: string, serverId?: string|null, description?: string|null} $input */
    public function update(User $user, string $uuid, array $input): Environment
    {
        return DB::transaction(function () use ($user, $uuid, $input): Environment {
            $environment = $this->find($user, $uuid) ?? throw (new ModelNotFoundException)->setModel(Environment::class);
            $project = $environment->project()->firstOrFail();
            $workspace = $this->projects->workspace($user, $project->workspace()->firstOrFail()->uuid);
            $project = $this->projects->visible($user)->whereKey($project->getKey())->lockForUpdate()->firstOrFail();
            $environment = $this->visible($user)->whereKey($environment->getKey())->lockForUpdate()->firstOrFail();
            $this->validateName($project, $input['name'], $environment->getKey());
            $changes = ['name' => $input['name']];
            if (array_key_exists('serverId', $input)) {
                $changes['server_id'] = $this->serverId($user, $input['serverId'], $workspace->getKey());
            }
            if (array_key_exists('description', $input)) {
                $changes['description'] = $input['description'];
            }
            $environment->update($changes);

            return $environment;
        });
    }

    public function delete(User $user, string $uuid): bool
    {
        return DB::transaction(function () use ($user, $uuid): bool {
            $environment = $this->find($user, $uuid) ?? throw (new ModelNotFoundException)->setModel(Environment::class);
            $this->projects->workspace($user, $environment->project()->firstOrFail()->workspace()->firstOrFail()->uuid);
            $environment = $this->visible($user)->whereKey($environment->getKey())->lockForUpdate()->firstOrFail();
            $environment->delete();

            return true;
        });
    }

    private function serverId(User $user, ?string $uuid, int $workspaceId): ?int
    {
        if ($uuid === null) {
            return null;
        }
        if (! Str::isUuid($uuid)) {
            throw ValidationException::withMessages(['serverId' => 'The selected server is invalid.']);
        }
        $server = $this->servers->find($user, $uuid);
        if (! $server || $server->workspace_id !== $workspaceId) {
            throw ValidationException::withMessages(['serverId' => 'The selected server is invalid.']);
        }

        return $server->getKey();
    }

    private function validateName(Project $project, string $name, ?int $except = null): void
    {
        $query = Environment::withTrashed()->where('project_id', $project->getKey())->where('name', $name);
        if ($except !== null) {
            $query->whereKeyNot($except);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['name' => 'The name has already been taken.']);
        }
    }
}
