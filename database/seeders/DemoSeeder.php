<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\WorkspaceRole;
use App\Models\Deployment;
use App\Models\DeploymentLogEntry;
use App\Models\Environment;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Arr;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $admin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $developer = User::factory()->create([
            'name' => 'developer',
            'email' => 'dev@example.com'
        ]);

        $viewer = User::factory()->create([
            'name' => 'viewer',
            'email' => 'viewer@example.com'
        ]);

        Workspace::factory()
            ->count(30)
            ->has(WorkspaceMembership::factory()->for($owner)->state(['role' => WorkspaceRole::Owner]), 'memberships')
            ->has(WorkspaceMembership::factory()->for($admin)->state(['role' => WorkspaceRole::Admin]), 'memberships')
            ->has(WorkspaceMembership::factory()->for($developer)->state(['role' => WorkspaceRole::Developer]), 'memberships')
            ->has(WorkspaceMembership::factory()->for($viewer)->state(['role' => WorkspaceRole::Viewer]), 'memberships')
            ->has(WorkspaceMembership::factory()->count(3)->state(fn () => [
                'role' => Arr::random(array_filter(
                    WorkspaceRole::cases(),
                    fn (WorkspaceRole $role) => $role !== WorkspaceRole::Owner,
                )),
            ]), 'memberships')
            ->create()
            ->each(function (Workspace $workspace) use ($owner): void {
                $server = Server::factory()->for($workspace)->create();

                $project = Project::factory()->for($workspace)->create();

                $environment = Environment::factory()
                    ->for($project)
                    ->for($server)
                    ->create();

                Deployment::factory()
                    ->for($workspace)
                    ->for($environment)
                    ->for($owner, 'requestedBy')
                    ->state(['server_id' => $server->id])
                    ->has(DeploymentLogEntry::factory()->count(3), 'logEntries')
                    ->create();
            });
    }
}
