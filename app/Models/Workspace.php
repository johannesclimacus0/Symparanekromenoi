<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use App\Enums\WorkspaceRole;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static WorkspaceFactory factory($count = null, $state = [])
 * @method static Builder<static>|Workspace newModelQuery()
 * @method static Builder<static>|Workspace newQuery()
 * @method static Builder<static>|Workspace query()
 *
 * @mixin \Eloquent
 */
#[Fillable('name')]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    /**
     * @return HasMany<WorkspaceMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'workspace_memberships',
        )->wherePivotNull('deleted_at')->withPivot('role')->withTimestamps();
    }

    /**
     * @return HasMany<WorkspaceMembership, $this>
     */
    public function ownerMembership(): HasMany
    {
        return $this->memberships()
            ->where('role', WorkspaceRole::Owner->value);
    }
}
