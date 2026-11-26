<?php

declare(strict_types=1);

namespace App\GraphQL\Queries\Workspace;

use App\Models\User;
use App\Models\Workspace;
use App\Modules\Workspaces\Queries\ListUserWorkspaces;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final readonly class MyWorkspaces
{
    public function __construct(private ListUserWorkspaces $query) {}

    /**
     * @param  array{first?: int, after?: string|null}  $args
     * @return array{edges: list<array{node: Workspace, cursor: string}>, pageInfo: array{hasNextPage: bool, endCursor: string|null}}
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context): array
    {
        $user = $context->user();
        if (! $user instanceof User) {
            throw new AuthenticationException;
        }
        $first = $args['first'] ?? 20;
        Validator::make(['first' => $first], ['first' => ['required', 'integer', 'min:1', 'max:100']])->validate();
        $query = $this->query->query($user);
        if (isset($args['after'])) {
            $uuid = base64_decode($args['after'], true);
            if ($uuid === false || ! Str::isUuid($uuid)) {
                throw ValidationException::withMessages(['after' => 'The cursor is invalid.']);
            }
            $query->where('workspaces.uuid', '>', $uuid);
        }
        $workspaces = $query->limit($first + 1)->get();
        $hasNextPage = $workspaces->count() > $first;
        $edges = array_values($workspaces->take($first)->map(fn (Workspace $workspace): array => [
            'node' => $workspace,
            'cursor' => base64_encode($workspace->uuid),
        ])->all());

        return ['edges' => $edges, 'pageInfo' => [
            'hasNextPage' => $hasNextPage,
            'endCursor' => $edges === [] ? null : $edges[count($edges) - 1]['cursor'],
        ]];
    }
}
