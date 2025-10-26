<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event_type')->index();
            $table->string('match_type')->default('all');
            $table->json('conditions');
            $table->string('action');
            $table->unsignedInteger('priority')->default(100)->index();
            $table->boolean('stop_on_match')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_rules');
    }
};
