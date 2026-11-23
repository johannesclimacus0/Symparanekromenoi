<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Workspace;

use App\Models\User;
use App\Modules\Workspaces\Actions\ManageInvitations;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class RevokeWorkspaceInvitation
{
    public function __construct(private ManageInvitations $action) {}

    /** @param array{input: array{id: string, invitationId: string}} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): bool
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->action->revoke($user, $args['input']['id'], $args['input']['invitationId']);
    }
}
