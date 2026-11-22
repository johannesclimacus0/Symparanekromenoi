<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployment_log_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('deployment_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('sequence');
            $table->enum('stream', ['stdout', 'stderr', 'system']);
            $table->text('message');

            $table->timestamp('recorded_at');

            $table->unique(['deployment_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployment_log_entries');
    }
};
