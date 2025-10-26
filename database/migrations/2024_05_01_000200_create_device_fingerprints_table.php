<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->morphs('authenticatable');
            $table->string('fingerprint');
            $table->string('device_name')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['fingerprint', 'authenticatable_type', 'authenticatable_id'],
                'device_fingerprints_authenticatable_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_fingerprints');
    }
};
