<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'type'], 'transactions_user_status_type_index');
            $table->index(['merchant_id', 'status', 'type'], 'transactions_merchant_status_type_index');
            $table->index(['agent_id', 'status', 'type'], 'transactions_agent_status_type_index');
            $table->index(['created_at', 'type'], 'transactions_created_type_index');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_user_status_type_index');
            $table->dropIndex('transactions_merchant_status_type_index');
            $table->dropIndex('transactions_agent_status_type_index');
            $table->dropIndex('transactions_created_type_index');
        });
    }
};
