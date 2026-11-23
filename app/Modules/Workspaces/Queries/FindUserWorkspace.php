<?php

declare(strict_types=1);

namespace App\Modules\Workspaces\Queries;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;

class FindUserWorkspace
{
    public function find(User $user, string $uuid): ?Workspace
    {
        if (! Str::isUuid($uuid)) {
            return null;
        }

        /** @var Workspace|null $workspace */
        $workspace = $user->workspaces()
            ->where('workspaces.uuid', $uuid)
            ->first();

        return $workspace;
    }
}
