<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Project;

use App\Models\User;
use App\Modules\Projects\ProjectManagement;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class DeleteProject
{
    public function __construct(private ProjectManagement $projects) {}

    /** @param array{id: string} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): bool
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->projects->delete($user, $args['id']);
    }
}
