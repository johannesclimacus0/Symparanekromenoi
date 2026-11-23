<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Project;

use App\Models\Project;
use App\Models\User;
use App\Modules\Projects\ProjectManagement;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class Projects
{
    public function __construct(private ProjectManagement $projects) {}

    /** @param array{workspaceId: string} $args
     * @return Builder<Project>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Builder
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        $query = $this->projects->visible($user);
        if (! Str::isUuid($args['workspaceId'])) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('workspace', fn ($builder) => $builder->where('uuid', $args['workspaceId']))->orderBy('id');
    }
}
