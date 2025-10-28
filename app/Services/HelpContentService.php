<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class HelpContentService
{
    protected string $basePath;

    protected string $manifestPath;

    protected ?array $manifestCache = null;

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
                    'question' => 'Which rate providers are used?',
                    'answer' => 'Rates are aggregated from tier-one liquidity providers with automated sanity checks to prevent stale data.',
                ],
                [
                    'question' => 'Can I lock rates for longer than 60 seconds?',
                    'answer' => 'Contact support to discuss tailored treasury workflows such as negotiated forward contracts.',
                ],
            ],
        ],
        [
            'slug' => 'withdrawals',
            'title' => 'Withdrawals',
            'icon' => 'las la-wallet',
            'description' => 'Disburse funds to bank accounts, mobile money, or card destinations with optional compliance workflows.',
            'endpoints' => [
                [
                    'method' => 'POST',
                    'path' => '/api/v1/withdrawals',
                    'description' => 'Initiate a withdrawal request with amount, currency, and destination details.',
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

    public function __construct(?string $basePath = null, ?string $manifestPath = null)
    {
        $this->basePath = $basePath ?? resource_path('docs/help');
        $this->manifestPath = $manifestPath ?? $this->basePath . DIRECTORY_SEPARATOR . 'manifest.json';
    }

    public function getApiCategories(?string $query = null): array
    {
        $normalizedQuery = $query ? Str::lower(trim($query)) : null;

        return collect($this->apiCategories)
            ->map(function (array $category) use ($normalizedQuery) {
                $faqs = collect($category['faqs'] ?? []);
                $faqText = $faqs
                    ->map(fn ($faq) => implode(' ', array_filter([$faq['question'] ?? '', $faq['answer'] ?? ''])))
                    ->implode(' ');
                $endpointText = collect($category['endpoints'] ?? [])
                    ->map(fn ($endpoint) => implode(' ', array_filter([$endpoint['method'] ?? '', $endpoint['path'] ?? '', $endpoint['description'] ?? ''])))
                    ->implode(' ');
                $keywords = Str::lower(preg_replace('/\s+/', ' ', trim(implode(' ', [
                    $category['title'] ?? '',
                    $category['description'] ?? '',
                    $endpointText,
                    $faqText,
                ]))));

                $category['keywords'] = $keywords;
                $category['matched_faqs'] = $faqs->all();

                if ($normalizedQuery) {
                    $category['matched_faqs'] = $faqs
                        ->filter(function ($faq) use ($normalizedQuery) {
                            $text = Str::lower(implode(' ', array_filter([$faq['question'] ?? '', $faq['answer'] ?? ''])));

                            return Str::contains($text, $normalizedQuery);
                        })
                        ->values()
                        ->all();

                    $category['matches'] = Str::contains($keywords, $normalizedQuery) || !empty($category['matched_faqs']);
                } else {
                    $category['matches'] = true;
                }

                return $category;
            })
            ->filter(fn (array $category) => $category['matches'])
            ->map(function (array $category) {
                unset($category['keywords'], $category['matches']);

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

    public function getSections(?string $language = null, ?string $query = null): array
    {
        $manifest = $this->manifest();
        $defaultLanguage = $manifest['default_language'] ?? 'en';
        $normalizedQuery = $query ? Str::lower(trim($query)) : null;

        return collect($manifest['sections'] ?? [])
            ->map(function (array $section) use ($language, $defaultLanguage) {
                $sectionDefault = $section['default_language'] ?? $defaultLanguage;
                $resolvedLanguage = $language ?? $sectionDefault;
                $latest = $this->resolveLatestVersion($section);

                return [
                    'id' => $section['id'],
                    'title' => $this->translateField($section['title'] ?? [], $resolvedLanguage, $sectionDefault),
                    'summary' => $this->translateField($section['summary'] ?? [], $resolvedLanguage, $sectionDefault),
                    'category' => $section['category'] ?? null,
                    'tags' => $section['tags'] ?? [],
                    'default_language' => $sectionDefault,
                    'available_languages' => array_keys($latest['languages'] ?? []),
                    'latest_version' => $latest['version'] ?? null,
                    'released_at' => $latest['released_at'] ?? null,
                    'keywords' => Str::lower(implode(' ', array_filter([
                        $this->translateField($section['title'] ?? [], $resolvedLanguage, $sectionDefault),
                        $this->translateField($section['summary'] ?? [], $resolvedLanguage, $sectionDefault),
                        $section['category'] ?? '',
                        implode(' ', $section['tags'] ?? []),
                    ]))),
                ];
            })
            ->filter(function (array $section) use ($normalizedQuery) {
                if (!$normalizedQuery) {
                    return true;
                }

                return Str::contains($section['keywords'], $normalizedQuery);
            })
            ->map(function (array $section) {
                unset($section['keywords']);

                return $section;
            })
            ->values()
            ->all();
    }

    public function getContent(string $sectionId, ?string $language = null, ?string $version = null): ?array
    {
        $manifest = $this->manifest();
        $defaultLanguage = $manifest['default_language'] ?? 'en';
        $section = collect($manifest['sections'] ?? [])->firstWhere('id', $sectionId);

        if (!$section) {
            return null;
        }

        $sectionDefault = $section['default_language'] ?? $defaultLanguage;
        $targetLanguage = $language ?? $sectionDefault;
        $versionData = $this->resolveVersion($section, $version);

        if (!$versionData) {
            return null;
        }

        $languages = $versionData['languages'] ?? [];
        $languageRecord = $languages[$targetLanguage] ?? ($languages[$sectionDefault] ?? reset($languages));

        if (!$languageRecord || empty($languageRecord['path'])) {
            return null;
        }

        $filePath = $this->basePath . DIRECTORY_SEPARATOR . $languageRecord['path'];

        if (!File::exists($filePath)) {
            return null;
        }

        $document = $this->parseDocument(File::get($filePath));
        $html = $this->toHtml($document['markdown']);

        $faqs = collect($versionData['faqs'] ?? [])->map(function (array $faq) use ($targetLanguage, $sectionDefault) {
            return [
                'id' => $faq['id'] ?? null,
                'question' => $this->translateField($faq['question'] ?? [], $targetLanguage, $sectionDefault),
                'anchor' => $faq['anchor'] ?? null,
            ];
        })->filter(fn ($faq) => $faq['id'] !== null)->values()->all();

        return [
            'section' => [
                'id' => $section['id'],
                'title' => $this->translateField($section['title'] ?? [], $targetLanguage, $sectionDefault),
                'summary' => $this->translateField($section['summary'] ?? [], $targetLanguage, $sectionDefault),
                'category' => $section['category'] ?? null,
                'tags' => $section['tags'] ?? [],
            ],
            'version' => $versionData['version'] ?? null,
            'language' => $targetLanguage,
            'released_at' => $versionData['released_at'] ?? null,
            'content' => $html,
            'raw' => $document['markdown'],
            'steps' => $document['steps'],
            'media' => $document['media'],
            'meta' => $document['meta'],
            'faqs' => $faqs,
        ];
    }

    public function resolveFaq(string $sectionId, string $faqId, ?string $language = null): ?array
    {
        $manifest = $this->manifest();
        $defaultLanguage = $manifest['default_language'] ?? 'en';
        $section = collect($manifest['sections'] ?? [])->firstWhere('id', $sectionId);

        if (!$section) {
            return null;
        }

        $sectionDefault = $section['default_language'] ?? $defaultLanguage;
        $language = $language ?? $sectionDefault;

        foreach ($section['versions'] ?? [] as $version) {
            foreach ($version['faqs'] ?? [] as $faq) {
                if (($faq['id'] ?? null) === $faqId) {
                    return [
                        'id' => $faqId,
                        'question' => $this->translateField($faq['question'] ?? [], $language, $sectionDefault),
                        'version' => $version['version'] ?? null,
                        'anchor' => $faq['anchor'] ?? null,
                        'language' => $language,
                    ];
                }
            }
        }

        return null;
    }

    protected function translateField(array $translations, string $language, string $fallback): ?string
    {
        if (empty($translations)) {
            return null;
        }

        $preferred = trim((string) ($translations[$language] ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        $default = trim((string) ($translations[$fallback] ?? ''));
        if ($default !== '') {
            return $default;
        }

        foreach ($translations as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    protected function manifest(): array
    {
        if ($this->manifestCache !== null) {
            return $this->manifestCache;
        }

        if (!File::exists($this->manifestPath)) {
            throw new RuntimeException('Help content manifest not found at ' . $this->manifestPath);
        }

        $decoded = json_decode(File::get($this->manifestPath), true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Help content manifest is malformed.');
        }

        return $this->manifestCache = $decoded;
    }

    protected function resolveLatestVersion(array $section): array
    {
        $versions = $section['versions'] ?? [];

        if (empty($versions)) {
            return [];
        }

        usort($versions, function ($a, $b) {
            $releasedA = $a['released_at'] ?? null;
            $releasedB = $b['released_at'] ?? null;

            if ($releasedA && $releasedB && $releasedA !== $releasedB) {
                return strcmp($releasedB, $releasedA);
            }

            return version_compare($b['version'] ?? '0.0.0', $a['version'] ?? '0.0.0');
        });

        return $versions[0];
    }

    protected function resolveVersion(array $section, ?string $version = null): ?array
    {
        $versions = $section['versions'] ?? [];

        if (empty($versions)) {
            return null;
        }

        if ($version) {
            foreach ($versions as $entry) {
                if (($entry['version'] ?? null) === $version) {
                    return $entry;
                }
            }
        }

        return $this->resolveLatestVersion($section) ?: null;
    }

    protected function parseDocument(string $raw): array
    {
        $rawWithoutBom = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $frontMatter = [];
        $markdown = $rawWithoutBom;

        if (str_starts_with($rawWithoutBom, "---\n") || str_starts_with($rawWithoutBom, "---\r\n")) {
            if (preg_match('/^---\s*\r?\n(.*?)\r?\n---\s*\r?\n?/s', $rawWithoutBom, $matches)) {
                $frontMatterRaw = $matches[1] ?? '';

                try {
                    $frontMatter = Yaml::parse($frontMatterRaw) ?? [];
                } catch (ParseException $exception) {
                    throw new RuntimeException('Unable to parse help content front matter: ' . $exception->getMessage(), 0, $exception);
                }

                $markdown = (string) substr($rawWithoutBom, strlen($matches[0] ?? ''));
            }
        }

        $markdown = ltrim($markdown);
        $structured = $this->normalizeStructuredData($frontMatter);

        return [
            'front_matter' => $frontMatter,
            'markdown' => $markdown,
            'steps' => $structured['steps'],
            'media' => $structured['media'],
            'meta' => $structured['meta'],
        ];
    }

    protected function normalizeStructuredData(array $frontMatter): array
    {
        $steps = [];
        $media = [];
        $meta = [];

        if (!empty($frontMatter['steps']) && is_array($frontMatter['steps'])) {
            foreach ($frontMatter['steps'] as $index => $step) {
                if (!is_array($step)) {
                    continue;
                }

                $steps[] = $this->normalizeStep($step, (int) $index);
            }
        }

        $mediaSources = $frontMatter['media'] ?? ($frontMatter['resources']['media'] ?? []);
        if ($mediaSources) {
            $media = $this->normalizeMediaCollection($mediaSources);
        }

        if (!empty($frontMatter['meta']) && is_array($frontMatter['meta'])) {
            $meta = $frontMatter['meta'];
        }

        if (isset($frontMatter['estimated_duration']) && !isset($meta['estimated_duration'])) {
            $meta['estimated_duration'] = $frontMatter['estimated_duration'];
        }

        return [
            'steps' => $steps,
            'media' => $media,
            'meta' => $meta,
        ];
    }

    protected function normalizeStep(array $step, int $index): array
    {
        $id = $step['id'] ?? 'step-' . ($index + 1);
        $content = isset($step['content']) ? (string) $step['content'] : '';

        return [
            'id' => $id,
            'title' => $step['title'] ?? ('Step ' . ($index + 1)),
            'summary' => $step['summary'] ?? null,
            'duration' => $step['duration'] ?? null,
            'html' => $content ? $this->toHtml($content) : '',
            'raw' => $content,
            'checklist' => array_values(array_filter($step['checklist'] ?? [])),
            'media' => $this->normalizeMediaCollection($step['media'] ?? []),
        ];
    }

    protected function normalizeMediaCollection($media): array
    {
        if (is_string($media)) {
            $media = [['url' => $media]];
        }

        if (isset($media['url'])) {
            $media = [$media];
        }

        if (!is_array($media)) {
            return [];
        }

        return collect($media)
            ->filter(fn ($item) => is_array($item) && !empty($item['url']))
            ->map(function (array $item) {
                $type = strtolower($item['type'] ?? $this->guessMediaType($item['url']));

                return [
                    'type' => $type,
                    'url' => $item['url'],
                    'label' => $item['label'] ?? ($item['caption'] ?? null),
                    'caption' => $item['caption'] ?? null,
                    'poster' => $item['poster'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    protected function guessMediaType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'mp4', 'mov', 'webm' => 'video',
            'gif' => 'gif',
            'jpg', 'jpeg', 'png', 'svg', 'webp' => 'image',
            'pdf' => 'pdf',
            default => 'link',
        };
    }

    protected function toHtml(string $markdown): string
    {
        return method_exists(Str::class, 'markdown')
            ? Str::markdown($markdown)
            : Str::of($markdown)->markdown();
    }
}
