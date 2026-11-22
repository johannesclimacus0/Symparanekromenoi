<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
