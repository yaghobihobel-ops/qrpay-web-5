<?php

namespace App\Http\Controllers\User;

use App\Constants\GlobalConst;
use App\Http\Controllers\Controller;
use App\Services\Orchestration\PaymentRouter;
use App\Services\Pricing\FeeEngine;
use App\Services\Pricing\Exceptions\PricingRuleNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function quote(Request $request, FeeEngine $feeEngine, PaymentRouter $paymentRouter): JsonResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'max:10'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_type' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'user_level' => ['nullable', 'string', 'max:255'],
            'destination_country' => ['nullable', 'string', 'max:3'],
        ]);

        $user = userGuard()['user'];
        $currency = strtoupper($validated['currency']);
        $amount = (float) $validated['amount'];
        $transactionType = $validated['transaction_type'];
        $userLevel = $validated['user_level'] ?? $this->resolveUserLevel($user);
        $provider = $validated['provider'] ?? null;

        $quote = null;
        $route = null;
        $decision = null;

        if ($provider === null && ! empty($validated['destination_country'])) {
            $decision = $paymentRouter->selectBestRoute([
                'currency' => $currency,
                'amount' => $amount,
                'destination_country' => strtoupper($validated['destination_country']),
            ]);

            if ($decision !== null) {
                $provider = $decision['provider'];
                $route = [
                    'provider' => $decision['provider'],
                    'priority' => $decision['priority'],
                    'fee' => $decision['fee'],
                    'sla' => $decision['sla'],
                ];
            }
        }

        if ($provider === null) {
            return response()->json([
                'error' => __('No provider matched for the given inputs.'),
            ], 422);
        }

        if (! $quote) {
            try {
                $quote = $feeEngine->quote(
                    currency: $currency,
                    provider: $provider,
                    transactionType: $transactionType,
                    userLevel: $userLevel,
                    amount: $amount,
                    options: [
                        'metadata' => $route ? ['route_id' => $decision['route_id']] : [],
                    ]
                );
            } catch (PricingRuleNotFoundException $exception) {
                return response()->json([
                    'error' => $exception->getMessage(),
                ], 422);
            }
        }

        return response()->json([
            'quote' => $quote->toArray(),
            'route' => $route,
        ]);
    }

    protected function resolveUserLevel($user): string
    {
        if ($user->is_sensitive) {
            return 'sensitive';
        }

        if ($user->kyc_verified == GlobalConst::VERIFIED) {
            return 'verified';
        }

        return 'standard';
    }
}
