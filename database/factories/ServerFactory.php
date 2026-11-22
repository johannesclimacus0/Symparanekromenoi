<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Server;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Server> */
class ServerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->word(),
            'address' => fake()->domainName(),
        ];
    }
}
