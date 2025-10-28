<?php

namespace App\Http\Controllers\PaymentGateway\QrPay\v1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DemoCheckoutController extends Controller
{
    public function index()
    {
        return view('qrpay-gateway.pages.checkout');
    }

    public function getToken(): object
    {
        $baseUrl = $this->resolveBaseUrl();
        $clientId = config('services.qrpay.client_id');
        $secretId = config('services.qrpay.secret_id');

        if (empty($clientId) || empty($secretId)) {
            return (object) [
                'code' => 500,
                'message' => __('QRPay credentials are not configured.'),
                'token' => '',
            ];
        }

        try {
            $response = Http::post($baseUrl . '/authentication/token', [
                'client_id' => $clientId,
                'secret_id' => $secretId,
            ]);
        } catch (Exception $exception) {
            report($exception);

            return (object) [
                'code' => 500,
                'message' => __('Failed to contact QRPay authentication service.'),
                'token' => '',
            ];
        }

        if (!$response->successful()) {
            $payload = $response->json();
            $status = $response->status();

            $message = data_get($payload, 'message.error.0')
                ?? data_get($payload, 'message.message')
                ?? data_get($payload, 'message')
                ?? data_get($payload, 'error')
                ?? __('Access token capture failed.');

            Log::warning('QRPay access token request failed.', [
                'status' => $status,
                'response' => $payload,
            ]);

            return (object) [
                'code' => $status,
                'message' => $message,
                'token' => '',
            ];
        }

        $payload = $response->json();

        return (object) [
            'code' => data_get($payload, 'message.code', 200),
            'message' => data_get($payload, 'type', 'success'),
            'token' => data_get($payload, 'data.access_token', ''),
        ];
    }

    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        $validated = $validator->validated();
        $tokenInfo = $this->getToken();

        if ($tokenInfo->code !== 200 || empty($tokenInfo->token)) {
            return back()->with(['error' => [$tokenInfo->message]]);
        }

        $baseUrl = $this->resolveBaseUrl();

        try {
            $response = Http::withToken($tokenInfo->token)
                ->post($baseUrl . '/payment/create', [
                    'amount' => $validated['amount'],
                    'currency' => 'USD',
                    'return_url' => route('merchant.checkout.success'),
                    'cancel_url' => route('merchant.checkout.cancel'),
                    'custom' => $this->customRandomString(10),
                ]);
        } catch (Exception $exception) {
            report($exception);

            return back()->with(['error' => [__('Unable to contact the QRPay payment service.')]]);
        }

        if (!$response->successful()) {
            $payload = $response->json();
            $message = data_get($payload, 'message.error.0')
                ?? data_get($payload, 'message')
                ?? __('Unable to create the payment.');

            Log::warning('QRPay payment creation failed.', [
                'status' => $response->status(),
                'response' => $payload,
            ]);

            return back()->with(['error' => [$message]]);
        }

        $paymentUrl = data_get($response->json(), 'data.payment_url');

        if (empty($paymentUrl)) {
            return back()->with(['error' => [__('Payment URL was not provided by QRPay.')]]);
        }

        return redirect()->away($paymentUrl);
    }

    public function paySuccess(Request $request)
    {
        if ($request->input('type') === 'success') {
            return redirect()
                ->route('merchant.checkout.index')
                ->with(['success' => [__('Your payment was processed successfully.')]]);
        }

        return redirect()
            ->route('merchant.checkout.index')
            ->with(['error' => [__('Unexpected response received from QRPay.')]]);
    }

    public function payCancel()
    {
        return redirect()
            ->route('merchant.checkout.index')
            ->with(['error' => [__('Your payment was cancelled.')]]);
    }

    protected function customRandomString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $random = '';

        for ($index = 0; $index < $length; $index++) {
            $random .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $random;
    }

    protected function resolveBaseUrl(): string
    {
        return rtrim(config('services.qrpay.base_url', 'https://qrpay.appdevs.net/pay/sandbox/api/v1'), '/');
    }
}
