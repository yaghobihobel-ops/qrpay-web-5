<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_content_completions', function (Blueprint $table) {
            $table->id();
            $table->string('section_id');
            $table->string('version')->nullable();
            $table->string('language', 12)->nullable();
            $table->string('viewer_type')->nullable();
            $table->unsignedBigInteger('viewer_id')->nullable();
            $table->string('session_id')->nullable();
            $table->unsignedSmallInteger('total_steps')->default(0);
            $table->unsignedSmallInteger('completed_steps')->default(0);
            $table->string('status', 32)->default('in_progress');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('section_id');
            $table->index(['section_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_content_completions');
    }
};
