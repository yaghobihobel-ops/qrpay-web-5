<?php

namespace App\Http\Controllers\PaymentGateway\QrPay\v1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DemoCheckoutController extends Controller
{
    public function index()
    {
        return view('qrpay-gateway.pages.checkout');
    }

    public function getToken(): object
    {
        $baseUrl = rtrim(config('services.qrpay.base_url', 'https://qrpay.appdevs.net/pay/sandbox/api/v1'), '/');
        $clientId = (string) config('services.qrpay.client_id');
        $secretId = (string) config('services.qrpay.secret_id');

        if ($clientId === '' || $secretId === '') {
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

        $statusCode = $response->getStatusCode();
        $result = $response->json();

        if ($statusCode !== 200) {
            $message = data_get($result, 'message.error.0')
                ?? data_get($result, 'message')
                ?? __('Access token capture failed.');

            Log::warning('QRPay access token request failed.', [
                'status' => $statusCode,
                'response' => $result,
            ]);

            return (object) [
                'code' => $statusCode,
                'message' => sprintf('HTTP %d: %s', $statusCode, $message),
                'token' => '',
            ];
        }

        return (object) [
            'code' => data_get($result, 'message.code', 200),
            'message' => data_get($result, 'type', 'success'),
            'token' => data_get($result, 'data.access_token', ''),
        ];
    }

    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $tokenInfo = $this->getToken();

        if ($tokenInfo->code !== 200 || empty($tokenInfo->token)) {
            return back()->with(['error' => [$tokenInfo->message]]);
        }

        $baseUrl = rtrim(config('services.qrpay.base_url', 'https://qrpay.appdevs.net/pay/sandbox/api/v1'), '/');

        try {
            $response = Http::withToken($tokenInfo->token)->post($baseUrl . '/payment/create', [
                'amount' => (float) $validated['amount'],
                'currency' => config('services.qrpay.currency', 'USD'),
                'return_url' => route('merchant.checkout.success'),
                'cancel_url' => route('merchant.checkout.cancel'),
                'custom' => $this->generateReference(),
            ]);
        } catch (Exception $exception) {
            report($exception);

            return back()->with(['error' => [__('Unable to initiate payment. Please try again.')]]);
        }

        $payload = $response->json();
        $paymentUrl = data_get($payload, 'data.payment_url');

        if ($response->failed() || empty($paymentUrl)) {
            Log::warning('QRPay payment initiation failed.', [
                'status' => $response->getStatusCode(),
                'response' => $payload,
            ]);

            $message = data_get($payload, 'message.error.0')
                ?? data_get($payload, 'message')
                ?? __('Something went wrong. Please try again later.');

            return back()->with(['error' => [$message]]);
        }

        return redirect()->away($paymentUrl);
    }

    public function paySuccess(Request $request)
    {
        if ($request->input('type') === 'success') {
            return redirect()
                ->route('merchant.checkout.index')
                ->with(['success' => [__('Your payment completed successfully.')]]);
        }

        return redirect()
            ->route('merchant.checkout.index')
            ->with(['error' => [__('Unable to verify payment status.')]]);
    }

    public function payCancel()
    {
        return redirect()
            ->route('merchant.checkout.index')
            ->with(['error' => [__('Your payment was cancelled.')]]);
    }

    protected function generateReference(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $reference = '';

        for ($i = 0; $i < $length; $i++) {
            $reference .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $reference;
    }
}
