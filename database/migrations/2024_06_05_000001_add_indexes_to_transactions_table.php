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
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('status');
            $table->index('merchant_id');
            $table->index('user_id');
            $table->index('agent_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['merchant_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['created_at']);
        });
    }
};
