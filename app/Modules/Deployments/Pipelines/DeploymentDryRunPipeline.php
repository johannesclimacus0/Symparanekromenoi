<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Pipelines;

use App\Models\Deployment;
use App\Modules\Deployments\Pipelines\Pipes\ValidateServerAddress;
use App\Modules\Deployments\Pipelines\Pipes\ValidateSnapshot;
use App\Modules\Deployments\Pipelines\Pipes\ValidateTargetConfiguration;
use Illuminate\Pipeline\Pipeline;

final readonly class DeploymentDryRunPipeline
{
    public function __construct(
        private Pipeline $pipeline,
    ) {}

    public function handle(Deployment $deployment): Deployment
    {
        return $this->pipeline
            ->send($deployment)
            ->through([
                ValidateSnapshot::class,
                ValidateServerAddress::class,
                ValidateTargetConfiguration::class,
            ])
            ->thenReturn();
    }
}
