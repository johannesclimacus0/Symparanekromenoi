<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DeploymentLogStream;
use App\Models\Deployment;
use App\Models\DeploymentLogEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeploymentLogEntry> */
class DeploymentLogEntryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'deployment_id' => Deployment::factory(),
            'sequence' => fake()->unique()->numberBetween(1, 2147483647),
            'stream' => DeploymentLogStream::System,
            'message' => fake()->sentence(),
            'recorded_at' => now(),
        ];
    }
}
