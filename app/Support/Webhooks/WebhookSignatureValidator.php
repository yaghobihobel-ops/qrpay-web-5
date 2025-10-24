<?php

namespace App\Support\Webhooks;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WebhookSignatureValidator
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(protected array $config)
    {
    }

    /**
     * Validate the webhook payload against the configured HMAC secret.
     *
     * @param array<string, array<int, string>> $headers
     */
    public function validate(
        string $countryCode,
        string $channel,
        string $providerKey,
        array $headers,
        string $rawBody
    ): WebhookValidationResult {
        $countryCode = strtoupper($countryCode);
        $channel = strtolower($channel);

        $providerConfig = Arr::get(
            $this->config,
            sprintf('providers.%s.%s.%s', $countryCode, $channel, $providerKey),
            []
        );

        $algorithm = $providerConfig['algorithm'] ?? $this->config['default_algorithm'] ?? 'sha256';
        $signatureHeader = $providerConfig['signature_header'] ?? $this->config['signature_header'] ?? 'X-Signature';
        $signatureHeader = Str::lower($signatureHeader);

        $normalizedHeaders = [];
        foreach ($headers as $name => $values) {
            $normalizedHeaders[Str::lower($name)] = $values;
        }

        $provided = $normalizedHeaders[$signatureHeader][0] ?? null;
        $secret = $providerConfig['secret'] ?? null;

        if (! $secret) {
            return new WebhookValidationResult(false, $provided, null, $algorithm, 'missing-secret');
        }

        $expected = hash_hmac($algorithm, $rawBody, $secret);

        if (! $provided) {
            return new WebhookValidationResult(false, null, $expected, $algorithm, 'missing-signature');
        }

        $isValid = hash_equals($expected, trim($provided));

        return new WebhookValidationResult(
            $isValid,
            trim($provided),
            $expected,
            $algorithm,
            $isValid ? null : 'signature-mismatch'
        );
    }
}
