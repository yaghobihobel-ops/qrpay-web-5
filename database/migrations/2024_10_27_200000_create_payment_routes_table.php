<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_routes', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('currency', 3);
            $table->unsignedInteger('priority')->default(1);
            $table->decimal('fee', 12, 4)->default(0);
            $table->decimal('max_amount', 18, 4)->nullable();
            $table->string('destination_country', 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['provider', 'currency', 'destination_country'], 'payment_routes_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_routes');
    }
};
