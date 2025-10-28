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
    public function index(): View
    {
        return view('qrpay-gateway.pages.checkout');
    }

    public function getToken(): object
    {
        $baseUrl = rtrim(config('services.qrpay.base_url', 'https://qrpay.appdevs.net/pay/sandbox/api/v1'), '/');
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

        $statusCode = $response->status();
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
                'message' => $message,
                'token' => '',
            ];
        }

        return (object) [
            'code' => data_get($result, 'message.code', 200),
            'message' => data_get($result, 'type', 'success'),
            'token' => data_get($result, 'data.access_token', ''),
            'base_url' => $baseUrl,
        ];
    }

    public function initiatePayment(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput();
        }

        $validated = $validator->validated();
        $tokenInfo = $this->getToken();

        if ($tokenInfo->code !== 200 || empty($tokenInfo->token)) {
            return back()->with([
                'error' => [$tokenInfo->message ?? __('Unable to obtain payment token.')],
            ]);
        }

        $baseUrl = $tokenInfo->base_url ?? rtrim(config('services.qrpay.base_url', ''), '/');
        $checkoutUrl = $baseUrl . '/payment/create';

        try {
            $response = Http::withToken($tokenInfo->token)
                ->acceptJson()
                ->post($checkoutUrl, [
                    'amount' => (float) $validated['amount'],
                    'currency' => 'USD',
                    'return_url' => route('merchant.checkout.success'),
                    'cancel_url' => route('merchant.checkout.cancel'),
                    'custom' => $this->generateReference(),
                ]);
        } catch (Exception $exception) {
            report($exception);

            return back()->with([
                'error' => [__('Unable to initiate payment. Please try again later.')],
            ]);
        }

        $payload = $response->json();

        if ($response->failed() || empty(data_get($payload, 'data.payment_url'))) {
            $error = data_get($payload, 'message.error.0')
                ?? data_get($payload, 'message')
                ?? __('Unable to initiate payment. Please try again later.');

            return back()->with([
                'error' => [$error],
            ]);
        }

        return redirect()->away(data_get($payload, 'data.payment_url'));
    }

    public function paySuccess(Request $request): RedirectResponse
    {
        if ($request->query('type') === 'success') {
            return redirect()->route('merchant.checkout.index')
                ->with(['success' => [__('Your payment completed successfully.')]]);
        }

        return redirect()->route('merchant.checkout.index')
            ->with(['error' => [__('Unable to verify payment status.')]]);
    }

    public function payCancel(): RedirectResponse
    {
        return redirect()->route('merchant.checkout.index')
            ->with(['error' => [__('Your payment was cancelled.')]]);
    }

    protected function generateReference(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $reference = '';
        $max = strlen($characters) - 1;

        for ($index = 0; $index < $length; $index++) {
            $reference .= $characters[random_int(0, $max)];
        }

        return $reference;
    }
}
