<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_memberships', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('role', WorkspaceRole::cases())->default('viewer');

            $table->timestamps();
            $table->softDeletes();

            $table->index('workspace_id');
        });

        DB::statement('
            CREATE UNIQUE INDEX workspace_memberships_active_user_unique
            ON workspace_memberships (user_id, workspace_id)
            WHERE deleted_at IS NULL
        ');

        DB::statement("
            CREATE UNIQUE INDEX workspace_memberships_one_active_owner
            ON workspace_memberships (workspace_id)
            WHERE role = 'owner' AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_memberships');
    }
};
