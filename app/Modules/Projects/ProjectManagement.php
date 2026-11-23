<?php

declare(strict_types=1);

namespace App\Modules\Projects;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProjectManagement
{
    /** @return Builder<Project> */
    public function visible(User $user): Builder
    {
        return Project::query()->whereIn('workspace_id', $user->workspaces()->select('workspaces.id'));
    }

    public function find(User $user, string $uuid): ?Project
    {
        return Str::isUuid($uuid) ? $this->visible($user)->where('uuid', $uuid)->first() : null;
    }

    public function workspace(User $user, string $uuid): Workspace
    {
        if (! Str::isUuid($uuid)) {
            throw (new ModelNotFoundException)->setModel(Workspace::class);
        }
        $workspace = Workspace::query()->where('uuid', $uuid)->whereIn('id', $user->workspaces()->select('workspaces.id'))->lockForUpdate()->firstOrFail();
        $member = $workspace->memberships()->where('user_id', $user->getKey())->firstOrFail();
        if (! in_array($member->role, [WorkspaceRole::Owner, WorkspaceRole::Admin, WorkspaceRole::Developer], true)) {
            throw new AuthorizationException;
        }

        return $workspace;
    }

    /** @param array{name: string, description?: string|null} $input */
    public function create(User $user, string $workspaceUuid, array $input): Project
    {
        return DB::transaction(function () use ($user, $workspaceUuid, $input): Project {
            $workspace = $this->workspace($user, $workspaceUuid);

            return Project::create([...$input, 'workspace_id' => $workspace->getKey()]);
        });
    }

    /** @param array{name: string, description?: string|null} $input */
    public function update(User $user, string $uuid, array $input): Project
    {
        return DB::transaction(function () use ($user, $uuid, $input): Project {
            $project = $this->find($user, $uuid) ?? throw (new ModelNotFoundException)->setModel(Project::class);
            $this->workspace($user, $project->workspace()->firstOrFail()->uuid);
            $project = $this->visible($user)->whereKey($project->getKey())->lockForUpdate()->firstOrFail();
            $project->update($input);

            return $project;
        });
    }

    public function delete(User $user, string $uuid): bool
    {
        return DB::transaction(function () use ($user, $uuid): bool {
            $project = $this->find($user, $uuid) ?? throw (new ModelNotFoundException)->setModel(Project::class);
            $this->workspace($user, $project->workspace()->firstOrFail()->uuid);
            $project = $this->visible($user)->whereKey($project->getKey())->lockForUpdate()->firstOrFail();
            $project->delete();

            return true;
        });
    }
}
