<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Workspace;

use App\Models\User;
use App\Models\Workspace as Model;
use App\Modules\Workspaces\Queries\FindUserWorkspace;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class Workspace
{
    public function __construct(
        private FindUserWorkspace $query
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
