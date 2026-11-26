<?php

namespace App\Modules\Deployments\Jobs;

use App\Models\Deployment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;

class RunDeploymentDryRun implements ShouldQueue
{
    use Queueable;

    public function __construct(private int $deploymentId) {}

    public function handle(): void
    {
        $deployment = Deployment::query()->findOrFail($this->deploymentId);

        Log::info('pupupu', [
            'deployment_id' => $deployment->id,
        ]);
    }
}
