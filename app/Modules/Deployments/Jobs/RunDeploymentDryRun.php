<?php

namespace App\Modules\Deployments\Jobs;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Models\DeploymentLogEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Gate;

class RunDeploymentDryRun implements ShouldQueue
{
    use Queueable;

    public function __construct(private int $deploymentId) {}

    public function handle(): void
    {
        $deployment = DB::transaction(function (): ?Deployment {
            $deployment = Deployment::query()
                ->lockForUpdate()
                ->findOrFail($this->deploymentId);

            if ($deployment->status !== DeploymentStatus::Queued) {
                return null;
            }

            $deployment->update([
                'status' => DeploymentStatus::Preparing,
                'started_at' => now(),
            ]);

            DeploymentLogEntry::create([
                'deployment_id' => $deployment->id,
                'sequence' => $this->nextSequence($deployment),
                'stream' => 'system',
                'message' => 'Preparing deployment.',
                'recorded_at' => now(),
            ]);

            return $deployment;
        });

        if ($deployment === null) {
            return;
        }

        $environment = $deployment->environment;
        $user = $deployment->requestedBy;

        if ($environment === null) {
            $this->failDeployment(
                'environment_not_found',
                'The environment was deleted.',
            );

            return;
        }

        $project = $environment->project;
        if ($project === null) {
            $this->failDeployment('project_unavailable', 'The project is no longer available.');

            return;
        }

        if ($project->workspace === null) {
            $this->failDeployment('workspace_unavailable', 'The workspace is no longer available.');

            return;
        }

        if ($user === null || Gate::forUser($user)->denies('requestDeploymentDryRun', $environment)) {
            $this->failDeployment(
                'access_revoked',
                'The user no longer has deployment permissions.',
            );

            return;
        }

        $server = $environment->server;

        if (
            $server === null
            || $server->trashed()
            || $server->workspace_id !== $deployment->workspace_id
            || $server->id !== $deployment->server_id
            || $project->workspace_id !== $deployment->workspace_id
        ) {
            $this->failDeployment(
                'target_changed',
                'The deployment target is no longer available.',
            );

            return;
        }

        DB::transaction(function (): void {
            $deployment = Deployment::query()
                ->lockForUpdate()
                ->findOrFail($this->deploymentId);

            if ($deployment->status !== DeploymentStatus::Preparing) {
                return;
            }

            $deployment->update([
                'status' => DeploymentStatus::Running,
            ]);

            DeploymentLogEntry::create([
                'deployment_id' => $deployment->id,
                'sequence' => $this->nextSequence($deployment),
                'stream' => 'system',
                'message' => 'Deployment target and permissions verified.',
                'recorded_at' => now(),
            ]);
        });
    }

    private function failDeployment(string $code, string $message): void {
        DB::transaction(function () use ($code, $message): void {
            $deployment = Deployment::query()
                ->lockForUpdate()
                ->findOrFail($this->deploymentId);

            if ($deployment->status !== DeploymentStatus::Preparing) {
                return;
            }

            $deployment->update([
                'status' => DeploymentStatus::Failed,
                'failure_code' => $code,
                'failure_message' => $message,
                'finished_at' => now(),
            ]);

            DeploymentLogEntry::create([
                'deployment_id' => $deployment->id,
                'sequence' => $this->nextSequence($deployment),
                'stream' => 'system',
                'message' => $message,
                'recorded_at' => now(),
            ]);
        });
    }

    private function nextSequence(Deployment $deployment): int
    {
        return (int) DeploymentLogEntry::query()
                ->where('deployment_id', $deployment->id)
                ->max('sequence') + 1;
    }
}
