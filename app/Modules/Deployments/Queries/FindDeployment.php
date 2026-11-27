<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Queries;

use App\Models\Deployment;
use App\Models\User;

class FindDeployment
{
    public function find(User $user, string $uuid): ?Deployment
    {
        return Deployment::query()
            ->where('uuid', $uuid)
            ->whereHas('workspace.memberships', fn ($query) => $query
                ->where('user_id', $user->getKey()))
            ->first();
    }
}
