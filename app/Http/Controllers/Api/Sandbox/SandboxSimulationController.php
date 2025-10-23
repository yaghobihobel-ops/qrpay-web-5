<?php

namespace App\Http\Controllers\Api\Sandbox;

use App\Http\Controllers\Controller;
use App\Services\Contracts\AirtimeProvider;
use App\Services\Contracts\BillPaymentProvider;
use App\Services\Contracts\ExchangeRateProvider;
use App\Services\Contracts\GiftCardProvider;
use App\Services\Contracts\PaymentProvider;
use Illuminate\Http\Request;

class SandboxSimulationController extends Controller
{
    public function __construct(
        protected PaymentProvider $payments,
        protected ExchangeRateProvider $exchangeRates,
        protected BillPaymentProvider $billers,
        protected AirtimeProvider $airtime,
        protected GiftCardProvider $giftCards
    ) {
    }

    public function payments()
    {
        return response()->json([
            'data' => $this->payments->listPayments(),
        ]);
    }

    public function storePayment(Request $request)
    {
        $payload = $request->validate([
            'amount' => 'required|numeric',
            'currency' => 'sometimes|string|size:3',
            'channel' => 'sometimes|string|max:20',
            'reference' => 'sometimes|string|max:64',
            'force_status' => 'sometimes|in:success,failed',
            'reason' => 'sometimes|string|max:255',
        ]);

        $record = $this->payments->processPayment($payload);

        return response()->json($record, 201);
    }

    public function exchangeRates()
    {
        return response()->json($this->exchangeRates->getLiveExchangeRates());
    }

    public function billers(Request $request)
    {
        $query = [];
        if ($request->filled('search')) {
            $query['search'] = $request->string('search')->toString();
        }

        return response()->json($this->billers->getBillers($query));
    }

    public function airtimeCountries(Request $request)
    {
        $iso = $request->query('iso');

        return response()->json([
            'data' => $this->airtime->getCountries($iso),
        ]);
    }

    public function giftCards()
    {
        return response()->json($this->giftCards->getProducts());
    }
}
