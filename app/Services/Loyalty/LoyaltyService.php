<?php

namespace App\Services\Loyalty;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyPointRule;
use App\Models\RewardEvent;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoyaltyService
{
    protected float $defaultMultiplier = 0.1;

    protected array $tiers = [
        [
            'name' => 'bronze',
            'label' => 'Bronze',
            'min_points' => 0,
            'benefits' => [
                'Earn points on every transaction',
            ],
        ],
        [
            'name' => 'silver',
            'label' => 'Silver',
            'min_points' => 1000,
            'benefits' => [
                'Priority customer support',
                'Weekend fee rebates',
            ],
        ],
        [
            'name' => 'gold',
            'label' => 'Gold',
            'min_points' => 5000,
            'benefits' => [
                'Complimentary expedited transfers',
                'Higher cash back multipliers',
            ],
        ],
        [
            'name' => 'platinum',
            'label' => 'Platinum',
            'min_points' => 15000,
            'benefits' => [
                'Dedicated relationship manager',
                'Exclusive partner discounts',
            ],
        ],
    ];

    public function getOrCreateAccount(User $user): LoyaltyAccount
    {
        return LoyaltyAccount::firstOrCreate(
            ['user_id' => $user->id],
            [
                'tier' => $this->determineTierName(0),
                'points_balance' => 0,
                'lifetime_points' => 0,
            ]
        );
    }

    public function calculatePoints(Transaction $transaction, ?string $provider = null): int
    {
        $amount = (float) ($transaction->request_amount ?? 0);
        if ($amount <= 0) {
            return 0;
        }

        $rule = LoyaltyPointRule::active()
            ->forProvider($provider)
            ->where(function ($query) use ($amount) {
                $query->where('min_volume', '<=', $amount)
                    ->where(function ($inner) use ($amount) {
                        $inner->whereNull('max_volume')
                            ->orWhere('max_volume', '>=', $amount);
                    });
            })
            ->orderByDesc('min_volume')
            ->first();

        $multiplier = $rule?->multiplier ?? $this->defaultMultiplier;
        $points = (int) round($amount * $multiplier);

        return max($points, 0);
    }

    public function applyTransaction(Transaction $transaction, ?string $provider = null): ?RewardEvent
    {
        if ($transaction->user === null) {
            return null;
        }

        $account = $this->getOrCreateAccount($transaction->user);
        $points = $this->calculatePoints($transaction, $provider);
        if ($points === 0) {
            return null;
        }

        return DB::transaction(function () use ($account, $points, $transaction, $provider) {
            $account->points_balance += $points;
            $account->lifetime_points += $points;
            $account->last_rewarded_at = Carbon::now();
            $account->tier = $this->determineTierName($account->lifetime_points);
            $account->save();

            return $account->rewardEvents()->create([
                'transaction_id' => $transaction->id,
                'provider' => $provider,
                'event_type' => 'earn',
                'points_change' => $points,
                'occurred_at' => Carbon::now(),
                'metadata' => [
                    'transaction_type' => $transaction->type,
                    'request_amount' => $transaction->request_amount,
                    'currency' => $transaction->currency?->currency_code,
                ],
            ]);
        });
    }

    public function redeemPoints(User $user, int $points, array $metadata = []): RewardEvent
    {
        $account = $this->getOrCreateAccount($user);
        if ($points <= 0) {
            throw new \InvalidArgumentException('Points to redeem must be greater than zero.');
        }

        if ($account->points_balance < $points) {
            throw new \RuntimeException('Insufficient loyalty points to redeem.');
        }

        return DB::transaction(function () use ($account, $points, $metadata) {
            $account->points_balance -= $points;
            $account->redeemed_rewards_count += 1;
            $account->save();

            return $account->rewardEvents()->create([
                'event_type' => 'redeem',
                'points_change' => -1 * $points,
                'occurred_at' => Carbon::now(),
                'metadata' => $metadata,
            ]);
        });
    }

    public function issueReward(User $user, string $rewardType, array $payload = []): RewardEvent
    {
        $account = $this->getOrCreateAccount($user);
        $rewardCode = $payload['code'] ?? Str::upper(Str::random(10));

        return DB::transaction(function () use ($account, $rewardType, $payload, $rewardCode) {
            $account->redeemed_rewards_count += 1;
            $account->last_rewarded_at = Carbon::now();
            $account->save();

            return $account->rewardEvents()->create([
                'event_type' => 'reward_issued',
                'points_change' => -1 * (int) ($payload['point_cost'] ?? 0),
                'reward_code' => $rewardCode,
                'reward_type' => $rewardType,
                'occurred_at' => Carbon::now(),
                'metadata' => array_merge($payload, [
                    'issued_at' => Carbon::now()->toDateTimeString(),
                ]),
            ]);
        });
    }

    public function getDashboardSummary(LoyaltyAccount $account): array
    {
        $currentTier = $this->getTier($account->tier);
        $nextTier = $this->getNextTier($account->tier);

        $pointsToNext = $nextTier
            ? max(0, $nextTier['min_points'] - $account->lifetime_points)
            : 0;

        $progressToNext = $nextTier
            ? $this->calculateProgress($account->lifetime_points, $currentTier['min_points'], $nextTier['min_points'])
            : 100;

        return [
            'balance' => $account->points_balance,
            'lifetime' => $account->lifetime_points,
            'tier' => $currentTier,
            'next_tier' => $nextTier,
            'points_to_next' => $pointsToNext,
            'progress_to_next' => $progressToNext,
            'recent_events' => $account->rewardEvents()->latest()->limit(5)->get(),
            'suggested_actions' => $this->buildSuggestedActions($account, $nextTier, $pointsToNext),
        ];
    }

    public function getSpecialOffers(LoyaltyAccount $account, int $limit = 3)
    {
        return LoyaltyCampaign::active()
            ->where('is_special_offer', true)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    protected function buildSuggestedActions(LoyaltyAccount $account, ?array $nextTier, int $pointsToNext): array
    {
        $suggestions = [];
        if ($nextTier) {
            $suggestions[] = __('Complete :points more points to unlock :tier status.', [
                'points' => $pointsToNext,
                'tier' => Arr::get($nextTier, 'label'),
            ]);
        }

        if ($account->points_balance > 0) {
            $suggestions[] = __('Redeem your points for fee discounts before they expire.');
        }

        if ($account->last_rewarded_at?->isPast()) {
            $suggestions[] = __('Make a payment with a featured provider to boost your multiplier.');
        }

        return $suggestions;
    }

    protected function determineTierName(int $points): string
    {
        $tiers = collect($this->tiers)->sortByDesc('min_points');
        foreach ($tiers as $tier) {
            if ($points >= $tier['min_points']) {
                return $tier['name'];
            }
        }

        return $this->tiers[0]['name'];
    }

    protected function getTier(string $tierName): array
    {
        $fallback = $this->tiers[0];
        foreach ($this->tiers as $tier) {
            if ($tier['name'] === $tierName) {
                return $tier;
            }
        }

        return $fallback;
    }

    protected function getNextTier(string $tierName): ?array
    {
        $tiers = collect($this->tiers)->sortBy('min_points')->values();
        $currentIndex = $tiers->search(fn ($tier) => $tier['name'] === $tierName);
        if ($currentIndex === false) {
            return $tiers->get(1);
        }

        return $tiers->get($currentIndex + 1);
    }

    protected function calculateProgress(int $points, int $currentMin, int $nextMin): int
    {
        $range = max($nextMin - $currentMin, 1);
        $progress = (($points - $currentMin) / $range) * 100;

        return (int) round(min(max($progress, 0), 100));
    }
}
