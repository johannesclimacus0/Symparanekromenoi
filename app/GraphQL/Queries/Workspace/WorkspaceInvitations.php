<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Workspace;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class WorkspaceInvitations
{
    /** @param array{id: string} $args
     * @return Builder<WorkspaceInvitation>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Builder
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        if (! Str::isUuid($args['id'])) {
            return WorkspaceInvitation::query()->whereRaw('1 = 0');
        }

        return WorkspaceInvitation::query()->whereHas('workspace', fn ($query) => $query
            ->where('uuid', $args['id'])
            ->whereHas('memberships', fn ($members) => $members->where('user_id', $user->getKey())
                ->whereIn('role', [WorkspaceRole::Owner->value, WorkspaceRole::Admin->value])))
            ->orderBy('id');
    }
}
