<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Workspace;

use App\Models\User;
use App\Modules\Workspaces\Actions\ManageWorkspace;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class TransferWorkspaceOwnership
{
    public function __construct(private ManageWorkspace $action) {}

    /** @param array{input: array{id: string, memberId: string}} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): bool
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->action->transfer($user, $args['input']['id'], $args['input']['memberId']);
    }
}
