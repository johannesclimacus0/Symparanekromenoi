<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use Database\Factories\EnvironmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Project|null $project
 * @property-read Server|null $server
 *
 * @method static \Database\Factories\EnvironmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Environment withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(['project_id', 'server_id', 'name', 'description'])]
class Environment extends Model
{
    /** @use HasFactory<EnvironmentFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
