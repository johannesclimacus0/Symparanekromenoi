<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Environment;

use App\Models\Environment;
use App\Models\Server;
use App\Models\User;
use App\Modules\Servers\ServerInventory;
use Illuminate\Auth\AuthenticationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class EnvironmentServer
{
    public function __construct(private ServerInventory $servers) {}

    /** @param array<string, mixed> $args */
    public function __invoke(Environment $environment, array $args, GraphQLContext $context): ?Server
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        $server = $environment->server;
        $project = $environment->project;
        if (! $server || ! $project || $server->workspace_id !== $project->workspace_id) {
            return null;
        }

        return $this->servers->find($user, $server->uuid);
    }
}
