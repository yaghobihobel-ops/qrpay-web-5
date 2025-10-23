<?php

namespace App\Services\Exchange;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class IranExchangeService extends BaseExchangeService
{
    public function __construct(protected array $config = [])
    {
    }

    public function fetchRates(array $symbols): array
    {
        $response = Http::baseUrl($this->config['base_url'] ?? 'https://api.nima.ir')
            ->acceptJson()
            ->get($this->config['endpoints']['rates'] ?? '/rates', [
                'symbols' => implode(',', array_map('strtoupper', $symbols)),
                'market' => $this->config['market'] ?? 'nima',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('NIMA exchange service request failed.');
        }

        $payload = $response->json();
        $entries = Arr::get($payload, $this->config['response_path'] ?? 'data.rates', []);
        $rates = $this->normalizeRates($entries);

        return $this->filterRates($rates, $symbols);
    }
}
