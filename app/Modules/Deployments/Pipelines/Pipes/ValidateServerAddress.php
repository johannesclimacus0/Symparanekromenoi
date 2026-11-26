<?php

declare(strict_types=1);

namespace App\Modules\Deployments\Pipelines\Pipes;

use App\Models\Deployment;
use App\Modules\Deployments\Exceptions\DeploymentValidationException;
use Closure;

final class ValidateServerAddress
{
    /**
     * @param  Closure(Deployment): Deployment  $next
     */
    public function handle(Deployment $deployment, Closure $next): Deployment
    {
        $address = $deployment->snapshot['server']['address'];

        if (! $this->isValidAddress($address)) {
            throw new DeploymentValidationException(
                'invalid_server_address',
                'The server address is invalid',
            );
        }

        return $next($deployment);
    }

    private function isValidAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if (preg_match('/^[0-9.]+$/D', $address) === 1) {
            return false;
        }

        return filter_var($address, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
