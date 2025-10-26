<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_content_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('section_id');
            $table->string('version')->nullable();
            $table->string('language', 12)->nullable();
            $table->string('viewer_type')->nullable();
            $table->unsignedBigInteger('viewer_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('rating', 16);
            $table->text('comment')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('section_id');
            $table->index('rating');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_content_feedback');
    }
};
