<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanction_lists', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable()->index();
            $table->string('name');
            $table->string('type')->default('individual')->index();
            $table->string('country')->nullable()->index();
            $table->json('identifiers')->nullable();
            $table->date('listed_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanction_lists');
    }
};
