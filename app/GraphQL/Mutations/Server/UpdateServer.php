<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Server;

use App\Models\Server;
use App\Models\User;
use App\Modules\Servers\ServerInventory;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Validator;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class UpdateServer
{
    public function __construct(private ServerInventory $inventory) {}

    /** @param array{input: array{id: string, name: string, address: string, description?: string|null}} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Server
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        Validator::make($args['input'], ['name' => ['required', 'string', 'max:255'], 'address' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000']])->validate();
        $input = $args['input'];
        unset($input['id']);

        return $this->inventory->update($user, $args['input']['id'], $input);
    }
}
