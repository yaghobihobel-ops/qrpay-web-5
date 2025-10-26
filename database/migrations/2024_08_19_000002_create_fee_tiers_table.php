<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_rule_id')->constrained('pricing_rules')->cascadeOnDelete();
            $table->decimal('min_amount', 24, 8)->nullable();
            $table->decimal('max_amount', 24, 8)->nullable();
            $table->string('fee_type')->default('percentage');
            $table->decimal('fee_amount', 18, 8)->default(0);
            $table->string('fee_currency', 12)->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();

            $table->index(['pricing_rule_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_tiers');
    }
};
