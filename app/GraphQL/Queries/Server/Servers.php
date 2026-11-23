<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Server;

use App\Models\Server;
use App\Models\User;
use App\Modules\Servers\ServerInventory;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class Servers
{
    public function __construct(private ServerInventory $inventory) {}

    /** @param array{workspaceId: string} $args
     * @return Builder<Server>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Builder
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        $query = $this->inventory->visible($user);
        if (! Str::isUuid($args['workspaceId'])) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('workspace', fn ($builder) => $builder->where('uuid', $args['workspaceId']))->orderBy('id');
    }
}
