<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditEntry;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditEntry> */
class AuditEntryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'actor_id' => null,
            'action' => 'workspace.created',
            'subject_type' => 'workspace',
            'subject_id' => fake()->uuid(),
            'metadata' => [],
            'occurred_at' => now(),
        ];
    }
}
