<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Workspace;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Modules\Workspaces\Actions\ManageWorkspace;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class ChangeWorkspaceMemberRole
{
    public function __construct(private ManageWorkspace $action) {}

    /** @param array{input: array{id: string, memberId: string, role: string}} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): bool
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->action->changeRole($user, $args['input']['id'], $args['input']['memberId'], WorkspaceRole::from($args['input']['role']));
    }
}
