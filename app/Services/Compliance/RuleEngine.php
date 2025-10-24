<?php

namespace App\Services\Compliance;

use App\Models\User;
use App\Services\Compliance\DTO\ComplianceDecision;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RuleEngine
{
    public function evaluate(User $user, array $kycPayload): ComplianceDecision
    {
        $region = $this->resolveRegion($user, $kycPayload);
        $rules = $this->mergeRules($region);

        $triggered = [];
        $risk = 0;
        $recommendations = [];

        foreach ($rules as $rule) {
            if ($this->ruleMatches($rule, $user, $kycPayload)) {
                $triggered[] = $rule;
                $risk += (int) ($rule['risk'] ?? 0);
                if (! empty($rule['resolution'])) {
                    $recommendations[] = $rule['resolution'];
                }
            }
        }

        $status = $this->determineStatus($risk);

        return new ComplianceDecision($status, $risk, $region, $triggered, array_values(array_unique($recommendations)));
    }

    protected function determineStatus(int $risk): string
    {
        $thresholds = config('compliance.thresholds');
        if ($risk >= ($thresholds['escalate'] ?? 70)) {
            return 'escalate';
        }

        if ($risk >= ($thresholds['review'] ?? 40)) {
            return 'review';
        }

        return 'pass';
    }

    protected function mergeRules(string $region): Collection
    {
        $rules = collect(config('compliance.regional_rules'));
        $global = collect($rules->get('GLOBAL', []));
        $regional = collect($rules->get($region, []));

        return $global->merge($regional);
    }

    protected function resolveRegion(User $user, array $kycPayload): string
    {
        $explicit = Arr::get($kycPayload, 'country');
        if ($explicit) {
            return Str::upper($explicit);
        }

        $documentCountry = Arr::get($kycPayload, 'document_country');
        if ($documentCountry) {
            return Str::upper($documentCountry);
        }

        $address = $user->address;
        if ($address && isset($address->country)) {
            return Str::upper($address->country);
        }

        return 'GLOBAL';
    }

    protected function ruleMatches(array $rule, User $user, array $kycPayload): bool
    {
        $conditions = $rule['conditions'] ?? [];
        if (empty($conditions)) {
            return false;
        }

        foreach ($conditions as $condition) {
            if (! $this->conditionSatisfied($condition, $user, $kycPayload)) {
                return false;
            }
        }

        return true;
    }

    protected function conditionSatisfied(array $condition, User $user, array $kycPayload): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? null;

        $context = [
            'user' => $user->toArray(),
            'kyc' => $kycPayload,
        ];

        $actual = data_get($context, $field);

        return match ($operator) {
            'equals' => $actual == $value,
            'strict_equals' => $actual === $value,
            'neq' => $actual != $this->resolveComparisonValue($value, $context),
            'in' => in_array($actual, (array) $value, true),
            'not_in' => ! in_array($actual, (array) $value, true),
            'gte' => $actual >= $value,
            'lte' => $actual <= $value,
            'gt' => $actual > $value,
            'lt' => $actual < $value,
            'missing' => empty($actual),
            default => false,
        };
    }

    protected function resolveComparisonValue($value, array $context)
    {
        if (is_string($value) && Str::contains($value, '.')) {
            return data_get($context, $value);
        }

        return $value;
    }
}
