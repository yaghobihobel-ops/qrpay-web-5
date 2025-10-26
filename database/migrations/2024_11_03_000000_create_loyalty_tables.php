<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('points_balance')->default(0);
            $table->unsignedBigInteger('lifetime_points')->default(0);
            $table->string('tier')->default('bronze');
            $table->unsignedInteger('redeemed_rewards_count')->default(0);
            $table->timestamp('last_rewarded_at')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('reward_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained('loyalty_accounts')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->nullable();
            $table->string('event_type');
            $table->bigInteger('points_change')->default(0);
            $table->string('reward_code')->nullable();
            $table->string('reward_type')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['loyalty_account_id', 'event_type']);
            $table->index('transaction_id');
        });

        Schema::create('loyalty_point_rules', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->nullable();
            $table->decimal('min_volume', 24, 8)->default(0);
            $table->decimal('max_volume', 24, 8)->nullable();
            $table->decimal('multiplier', 10, 4)->default(0);
            $table->string('reward_type')->default('points');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active']);
            $table->index(['min_volume', 'max_volume']);
        });

        Schema::create('loyalty_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('lifecycle');
            $table->string('trigger_event');
            $table->json('channels')->default(json_encode(['push']));
            $table->json('audience_filters')->nullable();
            $table->text('message_template');
            $table->string('cta_url')->nullable();
            $table->boolean('is_special_offer')->default(false);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['trigger_event', 'is_active']);
            $table->index('is_special_offer');
        });

        Schema::create('loyalty_campaign_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_campaign_id')->constrained('loyalty_campaigns')->cascadeOnDelete();
            $table->foreignId('loyalty_account_id')->nullable()->constrained('loyalty_accounts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel');
            $table->string('trigger_event');
            $table->string('test_variant')->nullable();
            $table->string('status')->default('queued');
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['loyalty_campaign_id', 'channel']);
            $table->index(['user_id', 'trigger_event']);
        });

        Schema::create('loyalty_campaign_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_campaign_id')->constrained('loyalty_campaigns')->cascadeOnDelete();
            $table->string('variant');
            $table->unsignedInteger('sample_size')->default(0);
            $table->unsignedInteger('deliveries')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->json('metrics')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['loyalty_campaign_id', 'variant']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_campaign_tests');
        Schema::dropIfExists('loyalty_campaign_runs');
        Schema::dropIfExists('loyalty_campaigns');
        Schema::dropIfExists('loyalty_point_rules');
        Schema::dropIfExists('reward_events');
        Schema::dropIfExists('loyalty_accounts');
    }
};
