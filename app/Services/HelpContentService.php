<?php

namespace App\Services;

use Illuminate\Support\Str;

class HelpContentService
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $apiCategories = [
        [
            'slug' => 'authentication',
            'title' => 'Authentication',
            'icon' => 'las la-user-shield',
            'description' => 'Securely onboard API clients, manage credentials, and retrieve OAuth tokens required for every request.',
            'endpoints' => [
                [
                    'method' => 'POST',
                    'path' => '/api/v1/auth/login',
                    'description' => 'Exchange client credentials for an access token and refresh token pair.',
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/v1/auth/refresh',
                    'description' => 'Renew an expired access token using a valid refresh token.',
                ],
                [
                    'method' => 'DELETE',
                    'path' => '/api/v1/auth/logout',
                    'description' => 'Invalidate the active token and revoke the current session.',
                ],
            ],
            'faqs' => [
                [
                    'question' => 'How do I authenticate requests from my server?',
                    'answer' => 'Send the client ID and secret to the /auth/login endpoint to obtain an access token, then include the token inside the Authorization header for subsequent calls.',
                ],
                [
                    'question' => 'When should refresh tokens be rotated?',
                    'answer' => 'Refresh tokens should be renewed on every refresh request and stored securely on the server-side application.',
                ],
                [
                    'question' => 'Which grant types are supported?',
                    'answer' => 'QRPay APIs use a confidential client credential flow optimized for server-to-server integrations.',
                ],
            ],
        ],
        [
            'slug' => 'payments',
            'title' => 'Payments',
            'icon' => 'las la-credit-card',
            'description' => 'Create and manage payment requests, reconcile settlements, and track transaction lifecycles.',
            'endpoints' => [
                [
                    'method' => 'POST',
                    'path' => '/api/v1/payments',
                    'description' => 'Create a charge or invoice for the supplied customer and amount.',
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/v1/payments/{trx}',
                    'description' => 'Retrieve the current status of a payment by transaction reference.',
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/v1/payments/{trx}/capture',
                    'description' => 'Capture a previously authorized payment when you are ready to settle.',
                ],
            ],
            'faqs' => [
                [
                    'question' => 'What currencies are supported for payments?',
                    'answer' => 'Payments inherit the merchant default currency. Multi-currency support is available when the Exchange module is enabled.',
                ],
                [
                    'question' => 'How can I reconcile asynchronous payment updates?',
                    'answer' => 'Subscribe to the webhook topic payment.updated to receive lifecycle events, or poll the payment status endpoint.',
                ],
                [
                    'question' => 'Is partial capture supported?',
                    'answer' => 'Yes, provide the capture amount in the request body to capture less than the authorized total.',
                ],
            ],
        ],
        [
            'slug' => 'exchange',
            'title' => 'Exchange',
            'icon' => 'las la-sync',
            'description' => 'Quote real-time conversion rates and exchange balances between supported wallets.',
            'endpoints' => [
                [
                    'method' => 'GET',
                    'path' => '/api/v1/exchange/rates',
                    'description' => 'Return the currently active buy and sell rates for every supported currency pair.',
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/v1/exchange/quote',
                    'description' => 'Generate a conversion quote using a source currency, destination currency, and amount.',
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/v1/exchange/confirm',
                    'description' => 'Lock a previously issued quote and commit the exchange.',
                ],
            ],
            'faqs' => [
                [
                    'question' => 'Do exchange quotes expire?',
                    'answer' => 'Quotes remain valid for 60 seconds. Re-request a quote if the window has expired before confirmation.',
                ],
                [
                    'question' => 'Are spreads configurable per merchant?',
                    'answer' => 'Yes, contact the QRPay support team to configure per-merchant exchange margins.',
                ],
                [
                    'question' => 'Can I simulate exchange rates in sandbox?',
                    'answer' => 'The sandbox environment provides deterministic test rates accessible through the same endpoints.',
                ],
            ],
        ],
        [
            'slug' => 'withdrawals',
            'title' => 'Withdrawals',
            'icon' => 'las la-university',
            'description' => 'Send payouts to bank accounts or mobile wallets and monitor disbursement statuses.',
            'endpoints' => [
                [
                    'method' => 'POST',
                    'path' => '/api/v1/withdrawals',
                    'description' => 'Create a withdrawal request to a saved beneficiary or provide account details inline.',
                ],
                [
                    'method' => 'GET',
                    'path' => '/api/v1/withdrawals/{trx}',
                    'description' => 'Inspect the processing status of a withdrawal.',
                ],
                [
                    'method' => 'POST',
                    'path' => '/api/v1/withdrawals/{trx}/cancel',
                    'description' => 'Attempt to cancel a pending withdrawal before it is handed to the payout provider.',
                ],
            ],
            'faqs' => [
                [
                    'question' => 'Which payout methods can I use?',
                    'answer' => 'Bank transfers, mobile money, and card payouts are supported depending on the connected provider.',
                ],
                [
                    'question' => 'How are withdrawal fees calculated?',
                    'answer' => 'Fees are applied based on the destination country and payout rail. Retrieve live fees from the /withdrawals endpoint response.',
                ],
                [
                    'question' => 'Can I retry a failed payout?',
                    'answer' => 'Yes, create a new withdrawal using the original reference so downstream reconciliation remains intact.',
                ],
            ],
        ],
    ];

    public function getApiCategories(?string $query = null): array
    {
        $normalizedQuery = $query ? Str::lower($query) : null;

        return collect($this->apiCategories)
            ->map(function (array $category) {
                $faqText = collect($category['faqs'] ?? [])->map(function ($faq) {
                    return ($faq['question'] ?? '') . ' ' . ($faq['answer'] ?? '');
                })->implode(' ');

                $endpointText = collect($category['endpoints'] ?? [])->map(function ($endpoint) {
                    return implode(' ', [$endpoint['method'] ?? '', $endpoint['path'] ?? '', $endpoint['description'] ?? '']);
                })->implode(' ');

                $keywords = $category['title'] . ' ' . ($category['description'] ?? '') . ' ' . $endpointText . ' ' . $faqText;

                $category['keywords'] = Str::lower(preg_replace('/\s+/', ' ', trim($keywords)));
                $category['matched_faqs'] = $category['faqs'];

                return $category;
            })
            ->filter(function (array $category) use ($normalizedQuery) {
                if (!$normalizedQuery) {
                    return true;
                }

                return Str::contains($category['keywords'], $normalizedQuery);
            })
            ->map(function (array $category) use ($normalizedQuery) {
                if (!$normalizedQuery) {
                    return $category;
                }

                $category['matched_faqs'] = collect($category['faqs'])
                    ->filter(function (array $faq) use ($normalizedQuery) {
                        return Str::contains(Str::lower(($faq['question'] ?? '') . ' ' . ($faq['answer'] ?? '')), $normalizedQuery);
                    })
                    ->values()
                    ->all();

                if (empty($category['matched_faqs'])) {
                    $category['matched_faqs'] = $category['faqs'];
                }

                return $category;
            })
            ->values()
            ->all();
    }

    public function getPostmanCollectionPath(): string
    {
        return 'docs/qrpay-api.postman_collection.json';
    }

    public function getApiOverviewVideoUrl(): string
    {
        return 'https://www.youtube.com/embed/ysz5S6PUM-U';
    }
}
