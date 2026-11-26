<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Environment;

use App\Models\User;
use App\Modules\Projects\EnvironmentManagement;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class DeleteEnvironment
{
    public function __construct(private EnvironmentManagement $environments) {}

    /** @param array{id: string} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): bool
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->environments->delete($user, $args['id']);
    }
}
