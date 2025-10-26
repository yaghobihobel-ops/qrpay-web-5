<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('currency', 12);
            $table->string('provider');
            $table->string('transaction_type');
            $table->string('user_level')->nullable();
            $table->string('fee_type')->default('percentage');
            $table->decimal('fee_amount', 18, 8)->default(0);
            $table->string('fee_currency', 12)->nullable();
            $table->decimal('min_amount', 24, 8)->nullable();
            $table->decimal('max_amount', 24, 8)->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('active')->default(true);
            $table->string('experiment')->nullable();
            $table->string('variant')->default('control');
            $table->json('metadata')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['currency', 'provider', 'transaction_type']);
            $table->index(['experiment', 'variant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
