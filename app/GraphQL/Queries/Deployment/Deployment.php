<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Deployment;

use App\Models\Deployment as Model;
use App\Models\User;
use App\Modules\Deployments\Queries\FindDeployment;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class Deployment
{
    public function __construct(
        private FindDeployment $query
    ) {}

    /** @param  array{id: string}  $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): ?Model
    {
        $user = $context->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->query->find($user, $args['id']);
    }
}
