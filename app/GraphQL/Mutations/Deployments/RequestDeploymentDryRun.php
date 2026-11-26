<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Deployments;

use App\Models\Deployment;
use App\Modules\Deployments\Actions\RequestDeploymentDryRun as Action;

final class RequestDeploymentDryRun
{
    public function __construct(
        private Action $action,
    ) {}

    public function __invoke(null $_, array $args): Deployment
    {
        return $this->action->handle(
            auth()->user(),
            $args['input']['environmentId'],
            $args['input']['idempotencyKey'],
        );
    }
}
