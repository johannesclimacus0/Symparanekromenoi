<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Workspace;

use App\Models\User;
use App\Models\WorkspaceMembership;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class WorkspaceMembers
{
    /** @param array{id: string} $args
     * @return Builder<WorkspaceMembership>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Builder
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        if (! Str::isUuid($args['id'])) {
            return WorkspaceMembership::query()->whereRaw('1 = 0');
        }

        return WorkspaceMembership::query()->whereHas('workspace', fn ($query) => $query
            ->where('uuid', $args['id'])
            ->whereHas('memberships', fn ($members) => $members->where('user_id', $user->getKey())))
            ->whereHas('user')
            ->orderBy('id');
    }
}
