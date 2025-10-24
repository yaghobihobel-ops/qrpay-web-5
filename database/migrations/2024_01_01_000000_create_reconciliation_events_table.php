<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_events', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 10);
            $table->string('channel');
            $table->string('provider_key');
            $table->string('provider_class')->nullable();
            $table->string('event_type')->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('status')->default('RECEIVED');
            $table->boolean('signature_valid')->default(false);
            $table->string('idempotency_key')->unique();
            $table->json('payload')->nullable();
            $table->json('headers')->nullable();
            $table->json('validation_details')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['country_code', 'channel']);
            $table->index(['provider_key', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_events');
    }
};
