<?php

namespace App\Modules\Deployments\Jobs;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Modules\Deployments\Actions\FailDeployment;
use App\Modules\Deployments\Exceptions\DeploymentValidationException;
use App\Modules\Deployments\Pipelines\DeploymentDryRunPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Throwable;

class RunDeploymentDryRun implements ShouldQueue
{
    use Queueable;

    public const TIMEOUT_SECONDS = 60;

    public int $timeout = self::TIMEOUT_SECONDS;

    public function __construct(private int $deploymentId) {}

    public function handle(DeploymentDryRunPipeline $pipeline): void
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

            $deployment->appendSystemLog('Preparing deployment.');

            return $deployment;
        });

        if ($deployment === null) {
            return;
        }

        try {
            $this->execute($deployment, $pipeline);
        } catch (Throwable $exception) {
            $this->failDeployment('worker_failed', 'The deployment worker failed.');
            throw $exception;
        }
    }

    private function execute(Deployment $deployment, DeploymentDryRunPipeline $pipeline): void
    {
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

        $running = DB::transaction(function (): ?Deployment {
            $deployment = Deployment::query()
                ->lockForUpdate()
                ->findOrFail($this->deploymentId);

            if ($deployment->status !== DeploymentStatus::Preparing) {
                return null;
            }

            $deployment->update([
                'status' => DeploymentStatus::Running,
            ]);

            $deployment->appendSystemLog('Starting configuration validation.');

            return $deployment;
        });

        if ($running === null) {
            return;
        }

        try {
            $pipeline->handle($running);
        } catch (DeploymentValidationException $exception) {
            $this->failDeployment(
                $exception->failureCode,
                $exception->getMessage(),
            );

            return;
        }

        $this->completeDeployment();
    }

    private function completeDeployment(): void
    {
        DB::transaction(function (): void {
            $deployment = Deployment::query()
                ->lockForUpdate()
                ->findOrFail($this->deploymentId);

            if ($deployment->status !== DeploymentStatus::Running) {
                return;
            }

            $deployment->update([
                'status' => DeploymentStatus::Succeeded,
                'finished_at' => now(),
            ]);

            $deployment->appendSystemLog('Configuration validation succeeded.');
        });
    }

    private function failDeployment(string $code, string $message): void
    {
        (new FailDeployment)->handle($this->deploymentId, $code, $message);
    }
}
