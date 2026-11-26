<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations\Server;

use App\Models\User;
use App\Modules\Servers\ServerInventory;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class DeleteServer
{
    public function __construct(private ServerInventory $inventory) {}

    /** @param array{id: string} $args */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): bool
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $this->inventory->delete($user, $args['id']);
    }
}
