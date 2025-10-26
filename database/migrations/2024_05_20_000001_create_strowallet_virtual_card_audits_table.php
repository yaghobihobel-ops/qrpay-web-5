<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strowallet_virtual_card_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('strowallet_virtual_card_id');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('attribute');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->timestamps();

            $table->foreign('strowallet_virtual_card_id')
                ->references('id')
                ->on('strowallet_virtual_cards')
                ->onDelete('cascade');

            $table->foreign('changed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strowallet_virtual_card_audits');
    }
};
