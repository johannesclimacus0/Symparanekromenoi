<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Environment;

use App\Models\Environment;
use App\Models\User;
use App\Modules\Projects\EnvironmentManagement;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class UpdateEnvironment
{
    public function __construct(private EnvironmentManagement $environments) {}

    /** @param array{input: array{id: string, name: string, serverId?: string|null, description?: string|null}} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Environment
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        Validator::make($args['input'], ['name' => ['required', 'string', 'max:100'], 'serverId' => ['nullable', 'string'], 'description' => ['nullable', 'string', 'max:10000']])->validate();
        $input = $args['input'];
        unset($input['id']);

        return $this->environments->update($user, $args['input']['id'], $input);
    }
}
