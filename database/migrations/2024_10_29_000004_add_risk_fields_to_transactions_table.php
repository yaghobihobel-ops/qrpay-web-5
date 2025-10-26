<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('risk_decision')->default('pending')->index();
            $table->decimal('risk_score', 8, 4)->nullable();
            $table->json('risk_metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['risk_decision', 'risk_score', 'risk_metadata']);
        });
    }
};
