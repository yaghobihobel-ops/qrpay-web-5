<?php

namespace App\Services\Exchange;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class ChinaFxService extends BaseExchangeService
{
    public function __construct(protected array $config = [])
    {
    }

    public function fetchRates(array $symbols): array
    {
        $response = Http::baseUrl($this->config['base_url'] ?? 'https://api.pboc.cn')
            ->acceptJson()
            ->get($this->config['endpoints']['rates'] ?? '/exchange/rates', [
                'symbols' => implode(',', array_map('strtoupper', $symbols)),
                'token' => $this->config['token'] ?? null,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('PBOC exchange service request failed.');
        }

        $payload = $response->json();
        $entries = Arr::get($payload, $this->config['response_path'] ?? 'data', []);
        $rates = $this->normalizeRates($entries);

        return $this->filterRates($rates, $symbols);
    }
}
