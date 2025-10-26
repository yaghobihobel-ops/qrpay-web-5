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
            $table->string('destination_country', 3);
            $table->unsignedInteger('priority')->default(0);
            $table->decimal('fee', 8, 4)->default(0);
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_routes');
    }
};
