<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use App\Enums\DeploymentLogStream;
use Database\Factories\DeploymentLogEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property DeploymentLogStream $stream
 * @property-read Deployment|null $deployment
 *
 * @method static \Database\Factories\DeploymentLogEntryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeploymentLogEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeploymentLogEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeploymentLogEntry query()
 *
 * @mixin \Eloquent
 */
#[WithoutTimestamps]
#[Fillable(['deployment_id', 'sequence', 'stream', 'message', 'recorded_at'])]
class DeploymentLogEntry extends Model
{
    /** @use HasFactory<DeploymentLogEntryFactory> */
    use HasFactory, HasUuidRouteKey;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'stream' => DeploymentLogStream::class,
            'recorded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Deployment, $this> */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
