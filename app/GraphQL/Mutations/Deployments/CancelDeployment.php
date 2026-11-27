<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Deployments;

use App\Models\Deployment;
use App\Modules\Deployments\Actions\CancelDeployment as Action;

final class CancelDeployment
{
    public function __construct(
        private Action $action,
    ) {}

    /** @param array{id: string} $args */
    public function __invoke(null $_, array $args): Deployment
    {
        return $this->action->handle(auth()->user(), $args['id']);
    }
}
