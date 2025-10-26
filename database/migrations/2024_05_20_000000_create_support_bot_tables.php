<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_bot_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('locale', 10)->nullable();
            $table->boolean('handoff_recommended')->default(false);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('support_bot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_bot_session_id')->constrained()->cascadeOnDelete();
            $table->enum('sender', ['user', 'bot', 'system'])->default('bot');
            $table->text('message');
            $table->string('intent')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('user_support_tickets', function (Blueprint $table) {
            $table->foreignId('support_bot_session_id')->nullable()->after('agent_id')->constrained('support_bot_sessions')->nullOnDelete();
            $table->timestamp('first_response_at')->nullable()->after('status');
            $table->timestamp('resolved_at')->nullable()->after('first_response_at');
            $table->unsignedTinyInteger('satisfaction_score')->nullable()->after('resolved_at');
            $table->text('satisfaction_comment')->nullable()->after('satisfaction_score');
        });
    }

    public function down(): void
    {
        Schema::table('user_support_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('support_bot_session_id');
            $table->dropColumn([
                'first_response_at',
                'resolved_at',
                'satisfaction_score',
                'satisfaction_comment',
            ]);
        });

        Schema::dropIfExists('support_bot_messages');
        Schema::dropIfExists('support_bot_sessions');
    }
};
