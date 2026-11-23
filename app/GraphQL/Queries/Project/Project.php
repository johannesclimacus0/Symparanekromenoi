<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Project;

use App\Models\Project as ProjectModel;
use App\Models\User;
use App\Modules\Projects\ProjectManagement;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class Project
{
    public function __construct(private ProjectManagement $projects) {}

    /** @param array{id: string} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): ?ProjectModel
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->projects->find($user, $args['id']);
    }
}
