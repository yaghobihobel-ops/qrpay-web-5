<?php

namespace App\Application\Providers\Payout;

use App\Application\Contracts\ProviderInterface;

class FlutterwavePayoutProvider implements ProviderInterface
{
    public function supports(string $driver): bool
    {
        return $driver === 'flutterwave';
    }

    public function handle(array $payload): array
    {
        $gateway = $payload['gateway'];
        $track = $payload['track'];
        $request = $payload['request'];
        $branchStatus = $payload['branch_status'] ?? false;

        $moneyOutData = $track->data;
        $credentials = $gateway->credentials;
        $secret_key = getPaymentCredentials($credentials, 'Secret key');
        $base_url = getPaymentCredentials($credentials, 'Base Url');
        $callback_url = url('/') . '/flutterwave/withdraw_webhooks';

        $ch = curl_init();
        $url = $base_url . '/transfers';
        $reference = generateTransactionReference();
        $data = [
            'account_bank' => $request->bank_name,
            'account_number' => $request->account_number,
            'amount' => $moneyOutData->will_get,
            'narration' => 'Withdraw from wallet',
            'currency' => $moneyOutData->gateway_currency,
            'reference' => $reference,
            'callback_url' => $callback_url,
            'debit_currency' => $moneyOutData->gateway_currency,
            'beneficiary_name' => $request->beneficiary_name ?? '',
        ];
        if ($branchStatus === true) {
            $data['destination_branch_code'] = $request->branch_code;
        }
        $headers = [
            'Authorization: Bearer ' . $secret_key,
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'status' => false,
                'message' => ['error' => [__("Something went wrong! Please try again.")]],
            ];
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            return [
                'status' => false,
                'message' => ['error' => [__("Something went wrong! Please try again.")]],
            ];
        }

        if (($result['status'] ?? null) === 'success') {
            return [
                'status' => true,
                'data' => [
                    'user_data' => $result['data'] ?? [],
                    'charges' => [],
                    'reference' => $reference,
                ],
                'message' => ['success' => [__('Withdraw money request send successful')]],
            ];
        }

        if (($result['status'] ?? null) === 'error') {
            if (isset($result['data'])) {
                $errors = $result['message'] . ',' . ($result['data']['complete_message'] ?? '');
            } else {
                $errors = $result['message'] ?? '';
            }

            return [
                'status' => false,
                'message' => ['error' => [$errors]],
            ];
        }

        return [
            'status' => false,
            'message' => ['error' => [$result['message'] ?? '']],
        ];
    }
}
