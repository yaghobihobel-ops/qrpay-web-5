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
            $table->string('provider')->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('transaction_type');
            $table->string('user_level')->default('standard');
            $table->string('base_currency', 10)->default('USD');
            $table->string('rate_provider')->nullable();
            $table->decimal('spread_bps', 8, 4)->default(0);
            $table->boolean('status')->default(true);
            $table->json('conditions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
