<?php

namespace App\Services\Exchange;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class TurkeyFxService extends BaseExchangeService
{
    public function __construct(protected array $config = [])
    {
    }

    public function fetchRates(array $symbols): array
    {
        $response = Http::baseUrl($this->config['base_url'] ?? 'https://api.tcmb.gov.tr')
            ->acceptJson()
            ->get($this->config['endpoints']['rates'] ?? '/rates', [
                'symbols' => implode(',', array_map('strtoupper', $symbols)),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('TCMB exchange service request failed.');
        }

        $payload = $response->json();
        $entries = Arr::get($payload, $this->config['response_path'] ?? 'result', []);
        $rates = $this->normalizeRates($entries);

        return $this->filterRates($rates, $symbols);
    }
}
