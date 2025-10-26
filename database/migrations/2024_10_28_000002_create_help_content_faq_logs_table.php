<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_content_faq_logs', function (Blueprint $table) {
            $table->id();
            $table->string('section_id');
            $table->string('faq_id');
            $table->string('version')->nullable();
            $table->string('language', 12)->nullable();
            $table->nullableMorphs('viewer');
            $table->string('session_id')->nullable();
            $table->string('action')->default('view');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['section_id', 'faq_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_content_faq_logs');
    }
};
