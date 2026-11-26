<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUuidRouteKey;
use App\Enums\WorkspaceRole;
use Carbon\CarbonImmutable;
use Database\Factories\WorkspaceInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property WorkspaceRole $role
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $revoked_at
 * @property-read User|null $invitedBy
 * @property-read Workspace|null $workspace
 *
 * @method static \Database\Factories\WorkspaceInvitationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WorkspaceInvitation query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['workspace_id', 'invited_by_id', 'email', 'role', 'token_hash', 'expires_at', 'accepted_at', 'revoked_at'])]
#[Hidden(['token_hash'])]
class WorkspaceInvitation extends Model
{
    /** @use HasFactory<WorkspaceInvitationFactory> */
    use HasFactory, HasUuidRouteKey;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
            'expires_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }
}
