<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use Database\Factories\AuditEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User|null $actor
 * @property-read Workspace|null $workspace
 *
 * @method static \Database\Factories\AuditEntryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEntry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEntry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditEntry query()
 *
 * @mixin \Eloquent
 */
#[WithoutTimestamps]
#[Fillable(['workspace_id', 'actor_id', 'action', 'subject_type', 'subject_id', 'metadata', 'occurred_at'])]
class AuditEntry extends Model
{
    /** @use HasFactory<AuditEntryFactory> */
    use HasFactory, HasUuidRouteKey;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
