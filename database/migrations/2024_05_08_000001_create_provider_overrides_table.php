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
        Schema::create('provider_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->string('provider')->nullable();
            $table->string('key');
            $table->json('value')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['domain', 'provider']);
            $table->index(['domain', 'key']);
            $table->unique(['domain', 'provider', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_overrides');
    }
};
