<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_routes', function (Blueprint $table) {
            $table->json('sla_thresholds')->nullable()->after('max_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_routes', function (Blueprint $table) {
            $table->dropColumn('sla_thresholds');
        });
    }
};
