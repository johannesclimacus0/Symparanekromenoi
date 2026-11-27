<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Pipelines\Pipes;

use App\Models\Deployment;
use App\Modules\Deployments\Exceptions\DeploymentValidationException;
use Closure;
use Illuminate\Support\Facades\Validator;

final class ValidateSnapshot
{
    /**
     * @param  Closure(Deployment): Deployment  $next
     */
    public function handle(Deployment $deployment, Closure $next): Deployment
    {
        $validator = Validator::make($deployment->snapshot, [
            'version' => ['required', 'integer', 'in:1'],
            'mode' => ['required', 'in:dry_run'],
            'project.uuid' => ['required', 'uuid'],
            'project.name' => ['required', 'string'],
            'environment.uuid' => ['required', 'uuid'],
            'environment.name' => ['required', 'string'],
            'server.uuid' => ['required', 'uuid'],
            'server.name' => ['required', 'string'],
            'server.address' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new DeploymentValidationException(
                'invalid_snapshot',
                $validator->errors()->first(),
            );
        }

        return $next($deployment);
    }
}
