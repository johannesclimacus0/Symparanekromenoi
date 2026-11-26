<?php

declare(strict_types=1);

namespace App\Modules\Workspaces\Actions;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateWorkspace
{
    public function handle(User $user, string $name): Workspace
    {
        return DB::transaction(function () use ($user, $name): Workspace {
            $workspace = Workspace::create(['name' => $name]);

            $workspace->memberships()->create([
                'user_id' => $user->getKey(),
                'role' => WorkspaceRole::Owner,
            ]);

            return $workspace;
        });
    }
}
