<?php

declare(strict_types=1);

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Modules\Deployments\Actions\FailDeployment;
use App\Modules\Deployments\Jobs\RunDeploymentDryRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('deployments:recover-stale', function (FailDeployment $fail): void {
    $cutoff = now()->subSeconds(RunDeploymentDryRun::TIMEOUT_SECONDS + 60);
    $stale = Deployment::query()->where('snapshot->mode', 'dry_run')
        ->whereIn('status', [DeploymentStatus::Preparing, DeploymentStatus::Running])
        ->where('started_at', '<=', $cutoff)->select('id')->lazyById();

    foreach ($stale as $deployment) {
        $fail->handle($deployment->id, 'worker_failed', 'The deployment worker timed out', $cutoff);
    }
});

Schedule::command('deployments:recover-stale')->everyMinute();
