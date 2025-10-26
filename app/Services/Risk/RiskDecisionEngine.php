<?php

namespace App\Services\Risk;

use App\Models\Risk\RiskRule;
use App\Models\Risk\RiskThreshold;
use App\Models\Risk\SanctionList;
use App\Models\Transaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonException;

class RiskDecisionEngine
{
    public function __construct(private OperationalDecisionEngine $operationalDecisionEngine)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function decide(Transaction $transaction): array
    {
        $context = $this->buildContext($transaction);
        $signals = [];

        if ($sanctionSignal = $this->evaluateSanctions($context)) {
            return $this->formatDecision('reject', 1.0, [$sanctionSignal], [
                'reason' => 'sanction_match',
            ]);
        }

        $ruleResult = $this->evaluateRules($context, $transaction->type);
        $signals = array_merge($signals, $ruleResult['signals']);

        $modelResult = $this->operationalDecisionEngine->evaluate($context['features']);
        $signals[] = [
            'type' => 'model',
            'name' => 'OperationalDecisionEngine',
            'metadata' => $modelResult,
        ];

        $score = (float) ($modelResult['fraud_score'] ?? $modelResult['score'] ?? 0.0);

        $thresholdResult = $this->applyThresholds('fraud_score', $score);
        $thresholdDecision = null;

        if ($thresholdResult !== null) {
            $thresholdDecision = $thresholdResult['decision'];
            $signals[] = $thresholdResult['signal'];
        }

        $finalDecision = $this->resolveDecision(
            $ruleResult['decision'],
            $thresholdDecision,
            $modelResult['decision'] ?? null
        );

        return $this->formatDecision($finalDecision, $score, $signals, [
            'rule_decision' => $ruleResult['decision'],
            'threshold_decision' => $thresholdDecision,
            'model_decision' => $modelResult['decision'] ?? null,
        ]);
    }

    /**
     * @param array<string, mixed> $context
     * @return array{decision: string|null, signals: array<int, array<string, mixed>>}
     */
    protected function evaluateRules(array $context, string $eventType): array
    {
        $rules = RiskRule::query()
            ->active()
            ->forEvent($eventType)
            ->orderBy('priority')
            ->get();

        $decision = null;
        $signals = [];

        foreach ($rules as $rule) {
            $conditions = collect($rule->conditions ?? []);

            if ($conditions->isEmpty()) {
                continue;
            }

            $match = $this->ruleMatches($context, $conditions, $rule->match_type);

            if (! $match) {
                continue;
            }

            $action = Str::of($rule->action)->lower()->value();
            $signals[] = [
                'type' => 'rule',
                'name' => $rule->name,
                'metadata' => [
                    'action' => $action,
                    'description' => $rule->description,
                ],
            ];

            $decision = $this->resolveDecision($decision, $action);

            if ($rule->stop_on_match) {
                break;
            }
        }

        return [
            'decision' => $decision,
            'signals' => $signals,
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>|null
     */
    protected function evaluateSanctions(array $context): ?array
    {
        $names = Collection::make([
            Arr::get($context, 'customer.name'),
            Arr::get($context, 'beneficiary.name'),
        ])
            ->filter()
            ->map(fn ($value) => Str::lower((string) $value))
            ->unique();

        if ($names->isEmpty()) {
            return null;
        }

        $matches = SanctionList::query()
            ->active()
            ->whereIn('name', $names->toArray())
            ->get();

        if ($matches->isEmpty()) {
            $matches = SanctionList::query()->active()->get()->filter(function (SanctionList $entry) use ($names) {
                $identifiers = Collection::make($entry->identifiers ?? [])->map(fn ($value) => Str::lower((string) $value));

                return $identifiers->intersect($names)->isNotEmpty();
            });
        }

        if ($matches->isEmpty()) {
            return null;
        }

        /** @var SanctionList $match */
        $match = $matches->first();

        return [
            'type' => 'sanction',
            'name' => $match->name,
            'metadata' => [
                'country' => $match->country,
                'reference' => $match->reference,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @param Collection<int, mixed> $conditions
     */
    protected function ruleMatches(array $context, Collection $conditions, string $matchType): bool
    {
        $evaluations = $conditions->map(function ($condition) use ($context) {
            $field = Arr::get($condition, 'field');
            $operator = Str::lower((string) Arr::get($condition, 'operator', 'eq'));
            $expected = Arr::get($condition, 'value');

            if ($field === null) {
                return false;
            }

            $actual = Arr::get($context, $field);

            return $this->compareValues($actual, $expected, $operator);
        });

        if (Str::lower($matchType) === 'any') {
            return $evaluations->contains(true);
        }

        return $evaluations->every(fn ($result) => $result === true);
    }

    protected function compareValues(mixed $actual, mixed $expected, string $operator): bool
    {
        return match ($operator) {
            'gte' => (float) $actual >= (float) $expected,
            'gt' => (float) $actual > (float) $expected,
            'lte' => (float) $actual <= (float) $expected,
            'lt' => (float) $actual < (float) $expected,
            'neq', 'ne' => (string) $actual !== (string) $expected,
            'in' => Collection::make((array) $expected)
                ->map(fn ($value) => Str::lower((string) $value))
                ->contains(Str::lower((string) $actual)),
            'contains' => Str::contains(Str::lower((string) $actual), Str::lower((string) $expected)),
            default => (string) $actual === (string) $expected,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function applyThresholds(string $metric, float $value): ?array
    {
        $threshold = RiskThreshold::query()
            ->active()
            ->forMetric($metric)
            ->orderBy('priority')
            ->get()
            ->first(function (RiskThreshold $threshold) use ($value) {
                return $this->compareValues($value, $threshold->value, Str::lower($threshold->comparator));
            });

        if (! $threshold) {
            return null;
        }

        return [
            'decision' => Str::lower($threshold->decision),
            'signal' => [
                'type' => 'threshold',
                'name' => $threshold->metric,
                'metadata' => [
                    'comparator' => $threshold->comparator,
                    'value' => $threshold->value,
                    'decision' => $threshold->decision,
                    'description' => $threshold->description,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildContext(Transaction $transaction): array
    {
        $details = $this->normaliseDetails($transaction->details);

        return [
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'attribute' => $transaction->attribute,
                'status' => $transaction->status,
                'payable' => (float) $transaction->payable,
                'request_amount' => (float) $transaction->request_amount,
                'currency_code' => optional($transaction->currency)->currency_code,
                'created_at' => optional($transaction->created_at)?->toIso8601String(),
            ],
            'customer' => [
                'id' => $transaction->user_id ?? $transaction->merchant_id ?? $transaction->agent_id,
                'type' => $this->resolveActorType($transaction),
                'name' => $this->resolveActorName($transaction),
                'email' => $this->resolveActorEmail($transaction),
                'country' => $this->resolveActorCountry($transaction),
            ],
            'beneficiary' => [
                'name' => Arr::get($details, 'beneficiary.name')
                    ?? Arr::get($details, 'receiver_name')
                    ?? Arr::get($details, 'bill_number'),
                'country' => Arr::get($details, 'beneficiary.country'),
            ],
            'details' => $details,
            'features' => $this->buildFeatureVector($transaction, $details),
        ];
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, float|int>
     */
    protected function buildFeatureVector(Transaction $transaction, array $details): array
    {
        $velocity = 0;

        if ($transaction->user_id) {
            $velocity = Transaction::query()
                ->where('user_id', $transaction->user_id)
                ->where('id', '!=', $transaction->id)
                ->where('created_at', '>=', now()->subDay())
                ->count();
        }

        $user = $transaction->user;
        $userAge = 0;

        if ($user && $user->created_at) {
            $userAge = now()->diffInDays($user->created_at);
        }

        $transactionCreatedHour = (int) optional($transaction->created_at)?->format('G');

        return [
            'amount' => (float) $transaction->payable,
            'request_amount' => (float) $transaction->request_amount,
            'transaction_velocity_24h' => (float) $velocity,
            'is_new_user' => $userAge > 0 && $userAge < 30 ? 1.0 : 0.0,
            'hour_of_day' => (float) $transactionCreatedHour,
            'is_cross_border' => $this->isCrossBorder($transaction, $details) ? 1.0 : 0.0,
            'fx_rate' => (float) (Arr::get($details, 'exchange.fx_rate') ?? 0.0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function normaliseDetails(mixed $details): array
    {
        if (is_array($details)) {
            return $details;
        }

        if (is_object($details)) {
            try {
                return json_decode(json_encode($details, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR) ?? [];
            } catch (JsonException) {
                return [];
            }
        }

        return [];
    }

    protected function resolveActorType(Transaction $transaction): ?string
    {
        if ($transaction->user_id) {
            return 'user';
        }

        if ($transaction->merchant_id) {
            return 'merchant';
        }

        if ($transaction->agent_id) {
            return 'agent';
        }

        return null;
    }

    protected function resolveActorName(Transaction $transaction): ?string
    {
        if ($transaction->user) {
            return $transaction->user->fullname;
        }

        if ($transaction->merchant) {
            return $transaction->merchant->name ?? $transaction->merchant->business_name ?? null;
        }

        if ($transaction->agent) {
            return $transaction->agent->fullname ?? $transaction->agent->name ?? null;
        }

        return null;
    }

    protected function resolveActorEmail(Transaction $transaction): ?string
    {
        if ($transaction->user) {
            return $transaction->user->email;
        }

        if ($transaction->merchant) {
            return $transaction->merchant->email;
        }

        if ($transaction->agent) {
            return $transaction->agent->email;
        }

        return null;
    }

    protected function resolveActorCountry(Transaction $transaction): ?string
    {
        $address = null;

        if ($transaction->user && $transaction->user->address) {
            $address = (array) $transaction->user->address;
        } elseif ($transaction->merchant && $transaction->merchant->address) {
            $address = (array) $transaction->merchant->address;
        } elseif ($transaction->agent && $transaction->agent->address) {
            $address = (array) $transaction->agent->address;
        }

        return $address['country'] ?? null;
    }

    protected function isCrossBorder(Transaction $transaction, array $details): bool
    {
        $origin = $this->resolveActorCountry($transaction);
        $destination = Arr::get($details, 'destination_country') ?? Arr::get($details, 'beneficiary.country');

        if (! $origin || ! $destination) {
            return false;
        }

        return Str::lower($origin) !== Str::lower((string) $destination);
    }

    /**
     * @param array<int, array<string, mixed>> $signals
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    protected function formatDecision(string $decision, float $score, array $signals, array $metadata = []): array
    {
        return [
            'decision' => Str::lower($decision),
            'score' => round($score, 4),
            'signals' => $signals,
            'metadata' => $metadata,
        ];
    }

    protected function resolveDecision(?string ...$decisions): string
    {
        $normalized = Collection::make($decisions)
            ->filter()
            ->map(fn ($decision) => Str::lower((string) $decision))
            ->values();

        if ($normalized->contains('reject')) {
            return 'reject';
        }

        if ($normalized->contains('manual_review')) {
            return 'manual_review';
        }

        if ($normalized->contains('approve')) {
            return 'approve';
        }

        return $normalized->first() ?? 'approve';
    }
}
