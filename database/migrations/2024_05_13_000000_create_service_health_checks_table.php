<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('service_type');
            $table->string('status');
            $table->decimal('latency_ms', 8, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['service_name', 'service_type']);
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_health_checks');
    }
};
