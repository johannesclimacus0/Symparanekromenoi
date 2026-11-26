<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Actions;

use App\Enums\DeploymentStatus;
use App\Models\AuditEntry;
use App\Models\Deployment;
use App\Models\DeploymentLogEntry;
use App\Models\Environment;
use App\Models\User;
use App\Modules\Deployments\Jobs\RunDeploymentDryRun;
use DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class RequestDeploymentDryRun
{
    public function handle(User $user, string $environmentUuid, string $idempotencyKey): Deployment {
        $environment = Environment::query()
            ->where('uuid', $environmentUuid)
            ->whereHas('project.workspace.memberships', fn ($query) =>
                $query->where('user_id', $user->id),
            )
            ->firstOrFail();

        Gate::forUser($user)->authorize('requestDeploymentDryRun', $environment);

        $project = $environment->project;
        $workspace = $project->workspace;

        $attributes = [
            'workspace_id' => $workspace->id,
            'requested_by_id' => $user->id,
            'idempotency_key' => $idempotencyKey,
        ];

        $deployment = Deployment::query()->where($attributes)->first();

        if ($deployment !== null) {
            $this->ensureSameEnvironment($deployment, $environment);

            return $deployment;
        }

        $server = $environment->server;

        if ($server === null || $server->trashed() || $server->workspace_id !== $workspace->id
        ) {
            throw ValidationException::withMessages([
                'environmentId' => 'The environment has no valid deployment target',
            ]);
        }

        $snapshot = [
            'version' => 1,
            'mode' => 'dry_run',
            'project' => [
                'uuid' => $project->uuid,
                'name' => $project->name,
            ],
            'environment' => [
                'uuid' => $environment->uuid,
                'name' => $environment->name,
            ],
            'server' => [
                'uuid' => $server->uuid,
                'name' => $server->name,
            ],
        ];

        return DB::transaction(function () use ($attributes, $environment, $server, $snapshot): Deployment {
            $deployment = Deployment::query()->createOrFirst(
                $attributes,
                [
                    'environment_id' => $environment->id,
                    'server_id' => $server->id,
                    'status' => DeploymentStatus::Queued,
                    'snapshot' => $snapshot,
                ],
            );

            $this->ensureSameEnvironment($deployment, $environment);

            if (! $deployment->wasRecentlyCreated) {
                return $deployment;
            }

            DeploymentLogEntry::create([
                'deployment_id' => $deployment->id,
                'sequence' => 1,
                'stream' => 'system',
                'message' => 'Deployment requested.',
                'recorded_at' => now(),
            ]);

            AuditEntry::create([
                'workspace_id' => $deployment->workspace_id,
                'actor_id' => $deployment->requested_by_id,
                'action' => 'deployment.requested',
                'subject_type' => 'deployment',
                'subject_id' => $deployment->uuid,
                'metadata' => [
                    'mode' => 'dry_run',
                    'environment_uuid' => $environment->uuid,
                    'server_uuid' => $server->uuid,
                ],
                'occurred_at' => now(),
            ]);

            RunDeploymentDryRun::dispatch($deployment->id)
                ->onQueue('default')
                ->afterCommit();

            return $deployment;
        });
    }

    private function ensureSameEnvironment(Deployment $deployment, Environment $environment): void {
        if ($deployment->environment_id !== $environment->id) {
            throw ValidationException::withMessages([
                'idempotencyKey' => 'This key has already been used for another environment',
            ]);
        }
    }
}
