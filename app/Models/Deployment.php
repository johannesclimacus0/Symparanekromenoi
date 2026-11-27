<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use App\Enums\DeploymentStatus;
use Database\Factories\DeploymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property DeploymentStatus $status
 * @property array<string, mixed> $snapshot
 * @property-read Environment|null $environment
 * @property-read Collection<int, DeploymentLogEntry> $logEntries
 * @property-read int|null $log_entries_count
 * @property-read User|null $requestedBy
 * @property-read Server|null $server
 * @property-read Workspace|null $workspace
 *
 * @method static \Database\Factories\DeploymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deployment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deployment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Deployment query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['workspace_id', 'environment_id', 'server_id', 'requested_by_id', 'status', 'snapshot', 'failure_code', 'failure_message', 'started_at', 'finished_at', 'idempotency_key'])]
class Deployment extends Model
{
    /** @use HasFactory<DeploymentFactory> */
    use HasFactory, HasUuidRouteKey;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => DeploymentStatus::class,
            'snapshot' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Environment, $this> */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    /** @return BelongsTo<Server, $this> */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    /** @return HasMany<DeploymentLogEntry, $this> */
    public function logEntries(): HasMany
    {
        return $this->hasMany(DeploymentLogEntry::class)->orderBy('sequence');
    }
}
