<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Environment;

use App\Models\Environment as EnvironmentModel;
use App\Models\User;
use App\Modules\Projects\EnvironmentManagement;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class Environment
{
    public function __construct(private EnvironmentManagement $environments) {}

    /** @param array{id: string} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): ?EnvironmentModel
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->environments->find($user, $args['id']);
    }
}
