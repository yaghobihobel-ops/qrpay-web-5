<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_screenings', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('region', 10)->default('GLOBAL');
            $table->string('status', 30)->default('pass');
            $table->unsignedInteger('risk_score')->default(0);
            $table->json('triggered_rules')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_screenings');
    }
};
