<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Environment;

use App\Models\Environment;
use App\Models\User;
use App\Modules\Projects\EnvironmentManagement;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class Environments
{
    public function __construct(private EnvironmentManagement $environments) {}

    /** @param array{projectId: string} $args
     * @return Builder<Environment>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): Builder
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        $query = $this->environments->visible($user);
        if (! Str::isUuid($args['projectId'])) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('project', fn ($builder) => $builder->where('uuid', $args['projectId']))->orderBy('id');
    }
}
