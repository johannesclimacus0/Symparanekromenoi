<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Actions;

use App\Enums\DeploymentStatus;
use App\Enums\WorkspaceRole;
use App\Models\Deployment;
use App\Models\User;
use App\Models\WorkspaceMembership;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelDeployment
{
    public function handle(User $user, string $uuid): Deployment
    {
        return DB::transaction(function () use ($user, $uuid): Deployment {
            $deployment = Deployment::query()->where('uuid', $uuid)
                ->whereHas('workspace.memberships', fn ($q) => $q->where('user_id', $user->id))
                ->lockForUpdate()->firstOrFail();
            $membership = WorkspaceMembership::query()->where('workspace_id', $deployment->workspace_id)
                ->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $allowed = match ($membership->role) {
                WorkspaceRole::Owner, WorkspaceRole::Admin => true,
                WorkspaceRole::Developer => $deployment->requested_by_id === $user->id,
                WorkspaceRole::Viewer => false,
            };
            if (! $allowed || ! $user->hasVerifiedEmail()) {
                throw new AuthorizationException;
            }
            if ($deployment->status === DeploymentStatus::Cancelled) {
                return $deployment;
            }
            if ($deployment->status !== DeploymentStatus::Queued) {
                throw ValidationException::withMessages(['id' => 'Only queued deployments can be cancelled.']);
            }
            $deployment->update(['status' => DeploymentStatus::Cancelled, 'finished_at' => now()]);
            $deployment->appendSystemLog('Deployment cancelled.');

            return $deployment;
        });
    }
}
