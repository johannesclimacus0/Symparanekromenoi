<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Workspace;

use App\Models\User;
use App\Models\Workspace;
use App\Modules\Workspaces\Actions\UpdateWorkspace as Action;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class UpdateWorkspace
{
    public function __construct(private Action $action) {}

    /** @param array{input: array{id: string, name: string}} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Workspace
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        Validator::make($args['input'], ['name' => ['required', 'string', 'max:100']])->validate();

        return $this->action->handle($user, $args['input']['id'], $args['input']['name']);
    }
}
