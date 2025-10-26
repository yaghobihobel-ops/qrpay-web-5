<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_content_views', function (Blueprint $table) {
            $table->id();
            $table->string('section_id');
            $table->string('version')->nullable();
            $table->string('language', 12)->nullable();
            $table->nullableMorphs('viewer');
            $table->string('session_id')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['section_id', 'version']);
            $table->index(['section_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_content_views');
    }
};
