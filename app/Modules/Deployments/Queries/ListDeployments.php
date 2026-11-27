<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Queries;

use App\Models\Deployment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ListDeployments
{
    /** @return Builder<Deployment> */
    public function forEnvironment(User $user, string $environmentUuid): Builder
    {
        return Deployment::query()
            ->whereHas('workspace.memberships', fn (Builder $query) =>
                $query->where('user_id', $user->id)
            )
            ->whereHas('environment', fn (Builder $query) =>
                $query->where('uuid', $environmentUuid)
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }
}
