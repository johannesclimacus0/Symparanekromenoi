<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable();

            $table->unique(['workspace_id', 'requested_by_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropUnique(['workspace_id', 'requested_by_id', 'idempotency_key']);

            $table->dropColumn('idempotency_key');
        });
    }
};
