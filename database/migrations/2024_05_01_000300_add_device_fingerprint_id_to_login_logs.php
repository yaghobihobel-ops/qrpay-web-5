<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['user_login_logs', 'agent_login_logs', 'merchant_login_logs', 'admin_login_logs'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('device_fingerprint_id')->nullable()->after('mac');
            });
        }
    }

    public function down(): void
    {
        foreach (['user_login_logs', 'agent_login_logs', 'merchant_login_logs', 'admin_login_logs'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('device_fingerprint_id');
            });
        }
    }
};
