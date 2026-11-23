<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use App\Enums\WorkspaceRole;
use Carbon\CarbonImmutable;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $workspace_id
 * @property int $user_id
 * @property WorkspaceRole $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read User|null $user
 * @property-read Workspace|null $workspace
 *
 * @method static \Database\Factories\WorkspaceMembershipFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership whereWorkspaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceMembership withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable('workspace_id', 'user_id', 'role')]
class WorkspaceMembership extends Model
{
    /** @use HasFactory<WorkspaceMembershipFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === WorkspaceRole::Owner;
    }

    public function roleValue(): string
    {
        return $this->role->value;
    }
}
