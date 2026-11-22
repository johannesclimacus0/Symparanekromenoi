<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\Project;
use App\Models\Server;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Deployment> */
class DeploymentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'server_id' => fn (array $attributes) => Server::factory()->create(['workspace_id' => $attributes['workspace_id']])->id,
            'environment_id' => function (array $attributes) {
                $project = Project::factory()->create(['workspace_id' => $attributes['workspace_id']]);

                return Environment::factory()->create(['project_id' => $project->id, 'server_id' => $attributes['server_id']])->id;
            },
            'requested_by_id' => null,
            'status' => DeploymentStatus::Queued,
            'snapshot' => [],
        ];
    }
}
