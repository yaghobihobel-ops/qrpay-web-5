<?php

namespace App\Services\Loyalty;

use App\Http\Helpers\PushNotificationHelper;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyCampaignRun;
use App\Models\LoyaltyCampaignTest;
use App\Models\User;
use App\Notifications\Loyalty\LoyaltyCampaignNotification;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CampaignService
{
    public function __construct(protected LoyaltyService $loyaltyService)
    {
    }

    public function trigger(string $event, User $user, array $context = []): array
    {
        $account = $this->loyaltyService->getOrCreateAccount($user);
        $campaigns = LoyaltyCampaign::with(['tests'])
            ->active()
            ->where('trigger_event', $event)
            ->get();

        $runs = [];
        foreach ($campaigns as $campaign) {
            if (! $this->passesAudienceFilters($campaign, $account, $context)) {
                continue;
            }

            $variant = $this->selectVariant($campaign);
            $variantName = $variant?->variant;

            foreach ((array) $campaign->channels as $channel) {
                $run = LoyaltyCampaignRun::create([
                    'loyalty_campaign_id' => $campaign->id,
                    'loyalty_account_id' => $account->id,
                    'user_id' => $user->id,
                    'channel' => $channel,
                    'trigger_event' => $event,
                    'test_variant' => $variantName,
                    'status' => 'queued',
                    'payload' => $context,
                ]);

                $runs[] = $this->dispatchChannel($campaign, $run, $user, $variant, $context);
            }
        }

        return $runs;
    }

    public function recordConversion(LoyaltyCampaignRun $run, array $metrics = []): LoyaltyCampaignRun
    {
        $run->status = 'converted';
        $run->responded_at = Carbon::now();
        $run->payload = array_merge($run->payload ?? [], ['conversion_metrics' => $metrics]);
        $run->save();

        if ($run->test_variant) {
            $test = $run->campaign->tests()->where('variant', $run->test_variant)->first();
            if ($test) {
                $test->registerConversion();
            }
        }

        return $run;
    }

    public function getActionableCampaigns(LoyaltyAccount $account, int $limit = 3)
    {
        return LoyaltyCampaign::active()
            ->whereIn('type', ['engagement', 'upsell', 'lifecycle'])
            ->orderByDesc('is_special_offer')
            ->limit($limit * 2)
            ->get()
            ->filter(fn ($campaign) => $this->passesAudienceFilters($campaign, $account))
            ->take($limit)
            ->values();
    }

    protected function dispatchChannel(LoyaltyCampaign $campaign, LoyaltyCampaignRun $run, User $user, ?LoyaltyCampaignTest $variant, array $context): LoyaltyCampaignRun
    {
        $run->status = 'processing';
        $run->sent_at = Carbon::now();
        $run->save();

        $message = $this->renderMessage($campaign, $context);
        $subject = $campaign->metadata['subject'] ?? $campaign->name;
        $payload = array_merge($campaign->metadata ?? [], [
            'cta_url' => $campaign->cta_url,
            'campaign_id' => $campaign->id,
            'variant' => $variant?->variant,
        ]);

        try {
            switch ($run->channel) {
                case 'push':
                    $this->sendPush($user, $subject, $message);
                    break;
                case 'sms':
                    $this->sendSms($user, $message, $payload);
                    break;
                case 'email':
                default:
                    $this->sendEmail($user, $subject, $message, $payload);
                    break;
            }

            $run->status = 'delivered';
            $run->delivered_at = Carbon::now();
            $run->save();

            if ($variant) {
                $variant->registerDelivery();
            }
        } catch (Throwable $exception) {
            Log::warning('Loyalty campaign dispatch failed', [
                'campaign_id' => $campaign->id,
                'run_id' => $run->id,
                'channel' => $run->channel,
                'message' => $exception->getMessage(),
            ]);

            $run->status = 'failed';
            $run->save();
        }

        return $run;
    }

    protected function passesAudienceFilters(LoyaltyCampaign $campaign, LoyaltyAccount $account, array $context = []): bool
    {
        $filters = $campaign->audience_filters ?? [];

        if (isset($filters['min_points']) && $account->lifetime_points < $filters['min_points']) {
            return false;
        }

        if (isset($filters['max_points']) && $account->lifetime_points > $filters['max_points']) {
            return false;
        }

        if (isset($filters['min_balance']) && $account->points_balance < $filters['min_balance']) {
            return false;
        }

        if (isset($filters['tier_in']) && ! in_array($account->tier, (array) $filters['tier_in'], true)) {
            return false;
        }

        if (isset($filters['tier_not_in']) && in_array($account->tier, (array) $filters['tier_not_in'], true)) {
            return false;
        }

        if (isset($filters['context_flags'])) {
            foreach ((array) $filters['context_flags'] as $flag) {
                if (! Arr::get($context, $flag, false)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function selectVariant(LoyaltyCampaign $campaign): ?LoyaltyCampaignTest
    {
        if (! $campaign->relationLoaded('tests')) {
            $campaign->load('tests');
        }

        $tests = $campaign->tests;
        if ($tests->isEmpty()) {
            return null;
        }

        $active = $tests->firstWhere('completed_at', null);
        return $active ?? $tests->sortByDesc('updated_at')->first();
    }

    protected function renderMessage(LoyaltyCampaign $campaign, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            $replacements[':'.$key] = is_scalar($value) ? (string) $value : json_encode($value);
        }

        $replacements[':campaign_name'] = $campaign->name;

        return strtr($campaign->message_template, $replacements);
    }

    protected function sendEmail(User $user, string $subject, string $message, array $payload): void
    {
        $user->notify(new LoyaltyCampaignNotification($subject, $message, $payload, 'email'));
    }

    protected function sendPush(User $user, string $subject, string $message): void
    {
        (new PushNotificationHelper([
            'users' => [$user->id],
            'user_type' => 'user',
        ]))->send($subject, $message);
    }

    protected function sendSms(User $user, string $message, array $payload): void
    {
        $phone = $user->full_mobile ?? $user->mobile ?? null;
        if (! $phone) {
            throw new \RuntimeException('Missing phone number for SMS dispatch.');
        }

        Notification::route('vonage', $phone)->notify(
            new LoyaltyCampaignNotification(__('New offer from :app', ['app' => config('app.name')]), $message, $payload, 'sms')
        );
    }
}
