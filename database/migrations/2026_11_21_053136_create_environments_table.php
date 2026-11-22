<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environments', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('project_id')->constrained()->restrictOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'name']);
            $table->index('server_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environments');
    }
};
