<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Actions;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

final class FailDeployment
{
    public function handle(int $id, string $code, string $message, ?DateTimeInterface $startedBefore = null): void
    {
        DB::transaction(function () use ($id, $code, $message, $startedBefore): void {
            $query = Deployment::query()->whereKey($id)
                ->whereIn('status', [DeploymentStatus::Preparing, DeploymentStatus::Running]);
            if ($startedBefore !== null) {
                $query->where('started_at', '<=', $startedBefore);
            }
            $deployment = $query->lockForUpdate()->first();
            if ($deployment === null) {
                return;
            }
            $deployment->update([
                'status' => DeploymentStatus::Failed,
                'failure_code' => $code,
                'failure_message' => $message,
                'finished_at' => now(),
            ]);
            $deployment->appendSystemLog($message);
        });
    }
}
