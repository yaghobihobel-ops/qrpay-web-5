<?php

namespace App\Services\Recommendations;

use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RecommendationFeatureExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $cutoff = Carbon::now()->subDays((int) config('recommendations.decay_period_days', 180));

        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->where('status', PaymentGatewayConst::STATUSSUCCESS)
            ->where('created_at', '>=', $cutoff)
            ->with(['currency', 'charge'])
            ->get();

        $count = $transactions->count();
        $volume = (float) $transactions->sum('request_amount');
        $perType = $transactions->groupBy('type')->map->count();
        $perCurrency = $transactions->groupBy(fn ($trx) => optional($trx->currency)->currency_code ?? 'UNKNOWN')
            ->map(fn (Collection $items) => [
                'count' => $items->count(),
                'volume' => (float) $items->sum('request_amount'),
            ]);

        $avgTicket = $count > 0 ? $volume / $count : 0.0;
        $dominantCurrency = $this->resolveDominantCurrency($perCurrency);
        $dominantType = $this->resolveDominantType($perType);

        $loyaltyScore = $this->calculateLoyaltyScore($count, $volume);
        $currencyConcentration = $volume > 0.0
            ? ($perCurrency[$dominantCurrency]['volume'] ?? 0.0) / $volume
            : 0.0;

        return [
            'transaction_count' => $count,
            'transaction_volume' => $volume,
            'per_type' => $perType->toArray(),
            'per_currency' => $perCurrency->toArray(),
            'average_ticket' => $avgTicket,
            'dominant_currency' => $dominantCurrency,
            'dominant_type' => $dominantType,
            'loyalty_score' => $loyaltyScore,
            'currency_concentration' => $currencyConcentration,
            'exchange_ratio' => $this->ratioForType($perType, PaymentGatewayConst::TYPEMONEYEXCHANGE, $count),
            'payment_ratio' => $this->ratioForType($perType, PaymentGatewayConst::TYPEMAKEPAYMENT, $count),
            'withdrawal_ratio' => $this->ratioForType($perType, PaymentGatewayConst::TYPEMONEYOUT, $count),
            'recent_transactions' => $transactions->sortByDesc('created_at')->take(10)->values()->all(),
        ];
    }

    protected function calculateLoyaltyScore(int $count, float $volume): float
    {
        $frequencyComponent = min(1.0, $count / max(1, (int) config('recommendations.min_transactions', 5)));
        $volumeComponent = min(1.0, log(1 + $volume) / log(1 + 5000));

        return round(($frequencyComponent * 0.4) + ($volumeComponent * 0.6), 4);
    }

    protected function resolveDominantCurrency(Collection $perCurrency): ?string
    {
        if ($perCurrency->isEmpty()) {
            return null;
        }

        return $perCurrency
            ->sortByDesc(fn ($entry) => $entry['volume'])
            ->keys()
            ->first();
    }

    protected function resolveDominantType(Collection $perType): ?string
    {
        if ($perType->isEmpty()) {
            return null;
        }

        return $perType->sortDesc()->keys()->first();
    }

    protected function ratioForType(Collection $perType, string $type, int $count): float
    {
        if ($count === 0) {
            return 0.0;
        }

        return round(($perType[$type] ?? 0) / $count, 4);
    }
}
