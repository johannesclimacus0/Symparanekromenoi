<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Environment;
use App\Models\User;

class EnvironmentPolicy
{
    public function requestDeploymentDryRun(User $user, Environment $environment): bool
    {
        return $user->hasVerifiedEmail()
            && $environment->project->workspace->memberships()
                ->where('user_id', $user->id)
                ->whereIn('role', [
                    WorkspaceRole::Owner->value,
                    WorkspaceRole::Admin->value,
                    WorkspaceRole::Developer->value,
                ])->exists();
    }
}
