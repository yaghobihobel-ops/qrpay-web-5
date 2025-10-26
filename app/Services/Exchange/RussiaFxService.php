<?php

namespace App\Services\Exchange;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class RussiaFxService extends BaseExchangeService
{
    public function __construct(protected array $config = [])
    {
    }

    public function fetchRates(array $symbols): array
    {
        $response = Http::baseUrl($this->config['base_url'] ?? 'https://www.cbr.ru')
            ->acceptJson()
            ->get($this->config['endpoints']['rates'] ?? '/v1/rates', [
                'symbols' => implode(',', array_map('strtoupper', $symbols)),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('CBR exchange service request failed.');
        }

        $payload = $response->json();
        $entries = Arr::get($payload, $this->config['response_path'] ?? 'rates', []);
        $rates = $this->normalizeRates($entries);

        return $this->filterRates($rates, $symbols);
    }
}
