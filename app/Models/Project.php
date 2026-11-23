<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Collection<int, Environment> $environments
 * @property-read int|null $environments_count
 * @property-read Workspace|null $workspace
 *
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(['workspace_id', 'name', 'description'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<Environment, $this> */
    public function environments(): HasMany
    {
        return $this->hasMany(Environment::class);
    }
}
