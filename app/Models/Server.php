<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use Database\Factories\ServerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read Workspace|null $workspace
 *
 * @method static \Database\Factories\ServerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Server withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable(['workspace_id', 'name', 'address', 'description'])]
class Server extends Model
{
    /** @use HasFactory<ServerFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
