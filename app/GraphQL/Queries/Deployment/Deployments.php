<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Deployment;

use App\Models\Deployment;
use App\Models\User;
use App\Modules\Deployments\Queries\ListDeployments;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Deployments
{
    public function __construct(private ListDeployments $query) {}

    /**
     * @param  array{environmentId: string}  $args
     * @return Builder<Deployment>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Builder
    {
        $user = $context->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->query->forEnvironment($user, $args['environmentId']);
    }
}
