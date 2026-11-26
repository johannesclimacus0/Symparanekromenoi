<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Project;

use App\Models\Project;
use App\Models\User;
use App\Modules\Projects\ProjectManagement;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class CreateProject
{
    public function __construct(private ProjectManagement $projects) {}

    /** @param array{input: array{workspaceId: string, name: string, description?: string|null}} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Project
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        Validator::make($args['input'], ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000']])->validate();
        $input = $args['input'];
        unset($input['workspaceId']);

        return $this->projects->create($user, $args['input']['workspaceId'], $input);
    }
}
