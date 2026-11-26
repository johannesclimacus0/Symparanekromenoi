<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Pipelines\Pipes;

use App\Models\Deployment;
use App\Modules\Deployments\Exceptions\DeploymentValidationException;
use Closure;

final class ValidateTargetConfiguration
{
    /**
     * @param  Closure(Deployment): Deployment  $next
     */
    public function handle(Deployment $deployment, Closure $next): Deployment
    {
        $deployment->refresh();

        $environment = $deployment->environment;
        $server = $environment?->server;
        $project = $environment?->project;

        $snapshot = $deployment->snapshot;

        if (
            $environment?->uuid !== $snapshot['environment']['uuid']
            || $project?->uuid !== $snapshot['project']['uuid']
            || $server?->uuid !== $snapshot['server']['uuid']
            || $server?->address !== $snapshot['server']['address']
        ) {
            throw new DeploymentValidationException(
                'configuration_changed',
                'The deployment configuration has changed since the request',
            );
        }

        return $next($deployment);
    }
}
