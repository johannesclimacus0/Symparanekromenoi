<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Modules\Workspaces\Actions\CreateWorkspace;

test('workspace creation rolls back when membership creation fails', function () {
    $user = User::factory()->create();

    WorkspaceMembership::creating(function (): void {
        throw new RuntimeException('membership creation failed');
    });

    try {
        expect(fn () => app(CreateWorkspace::class)->handle($user, 'Example'))
            ->toThrow(RuntimeException::class, 'membership creation failed')
            ->and(Workspace::query()->count())->toBe(0)
            ->and(WorkspaceMembership::query()->count())->toBe(0);

    } finally {
        WorkspaceMembership::flushEventListeners();
    }
});
