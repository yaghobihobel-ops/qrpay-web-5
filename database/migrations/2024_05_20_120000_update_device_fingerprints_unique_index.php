<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_fingerprints', function (Blueprint $table) {
            $table->dropUnique('device_fingerprints_fingerprint_unique');
            $table->unique([
                'authenticatable_type',
                'authenticatable_id',
                'fingerprint',
            ], 'device_fingerprints_authenticatable_fingerprint_unique');
        });
    }

    public function down(): void
    {
        Schema::table('device_fingerprints', function (Blueprint $table) {
            $table->dropUnique('device_fingerprints_authenticatable_fingerprint_unique');
            $table->unique('fingerprint');
        });
    }
};
