<?php

namespace App\Models\Pricing;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collections\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $currency
 * @property string $provider
 * @property string $transaction_type
 * @property string|null $user_level
 * @property string $fee_type
 * @property float $fee_amount
 * @property string|null $fee_currency
 * @property float|null $min_amount
 * @property float|null $max_amount
 * @property int $priority
 * @property bool $active
 * @property string|null $experiment
 * @property string $variant
 * @property CarbonInterface|null $starts_at
 * @property CarbonInterface|null $ends_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property Collection<int, FeeTier> $feeTiers
 */
class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'currency',
        'provider',
        'transaction_type',
        'user_level',
        'fee_type',
        'fee_amount',
        'fee_currency',
        'min_amount',
        'max_amount',
        'priority',
        'active',
        'experiment',
        'variant',
        'metadata',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:8',
        'min_amount' => 'decimal:8',
        'max_amount' => 'decimal:8',
        'priority' => 'integer',
        'active' => 'boolean',
        'metadata' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<FeeTier>
     */
    public function feeTiers()
    {
        return $this->hasMany(FeeTier::class)->orderBy('priority')->orderBy('min_amount');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function isActiveAt(?CarbonInterface $moment = null): bool
    {
        $moment ??= Carbon::now();

        if (! $this->active) {
            return false;
        }

        if ($this->starts_at && $moment->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $moment->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function matchesUserLevel(?string $userLevel): bool
    {
        if ($this->user_level === null || $this->user_level === '*' || $this->user_level === '') {
            return true;
        }

        return strcasecmp($this->user_level, (string) $userLevel) === 0;
    }

    public function matchesExperiment(?string $experiment, ?string $variant): bool
    {
        if ($this->experiment === null || $this->experiment === '') {
            return $experiment === null || $experiment === '';
        }

        if (! $experiment || strcasecmp($this->experiment, $experiment) !== 0) {
            return false;
        }

        if ($this->variant === null || $this->variant === '' || $this->variant === '*' ) {
            return true;
        }

        if (! $variant) {
            return false;
        }

        return strcasecmp($this->variant, $variant) === 0;
    }

    public function matchesAmount(float $amount): bool
    {
        if ($this->min_amount !== null && $amount < (float) $this->min_amount) {
            return false;
        }

        if ($this->max_amount !== null && $amount > (float) $this->max_amount) {
            return false;
        }

        return true;
    }

    public function resolveTier(float $amount): ?FeeTier
    {
        $tiers = $this->feeTiers;

        if ($tiers->isEmpty()) {
            return null;
        }

        return $tiers->filter(function (FeeTier $tier) use ($amount) {
            return $tier->matchesAmount($amount);
        })->sortBy('priority')->sortBy('min_amount')->first();
    }

    public function syncFeeTiers(array $tiers): void
    {
        $normalized = [];

        foreach ($tiers as $tier) {
            $tier = array_filter(Arr::only($tier, [
                'id',
                'min_amount',
                'max_amount',
                'fee_type',
                'fee_amount',
                'fee_currency',
                'priority',
            ]), function ($value) {
                return $value !== null && $value !== '';
            });

            if (! isset($tier['fee_amount'])) {
                continue;
            }

            $tier['fee_currency'] = $tier['fee_currency'] ?? $this->fee_currency ?? $this->currency;
            $tier['fee_type'] = $tier['fee_type'] ?? $this->fee_type;
            $tier['priority'] = isset($tier['priority']) ? (int) $tier['priority'] : 100;

            $normalized[] = $tier;
        }

        $ids = [];

        foreach ($normalized as $tier) {
            if (isset($tier['id'])) {
                $tierModel = $this->feeTiers()->whereKey($tier['id'])->first();
                if ($tierModel) {
                    $tierModel->update($tier);
                    $ids[] = $tierModel->id;
                    continue;
                }
            }

            $ids[] = $this->feeTiers()->create($tier)->id;
        }

        if (! empty($ids)) {
            $this->feeTiers()->whereNotIn('id', $ids)->delete();
        } else {
            $this->feeTiers()->delete();
        }
    }
}
