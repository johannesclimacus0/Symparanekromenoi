<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('environment_id')->constrained()->restrictOnDelete();
            $table->foreignId('server_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->enum('status', ['queued', 'preparing', 'running', 'succeeded', 'failed', 'cancelled']);
            $table->json('snapshot');
            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at', 'id']);
            $table->index(['environment_id', 'created_at', 'id']);
            $table->index('server_id');
            $table->index('requested_by_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
