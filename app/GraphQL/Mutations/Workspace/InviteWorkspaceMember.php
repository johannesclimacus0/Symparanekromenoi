<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Workspace;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Modules\Workspaces\Actions\ManageInvitations;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class InviteWorkspaceMember
{
    public function __construct(private ManageInvitations $action) {}

    /** @param array{input: array{id: string, email: string, role: string}} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): WorkspaceInvitation
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        Validator::make($args['input'], ['email' => ['required', 'email', 'max:255']])->validate();

        return $this->action->create($user, $args['input']['id'], $args['input']['email'], WorkspaceRole::from($args['input']['role']));
    }
}
