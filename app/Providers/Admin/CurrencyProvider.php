<?php

namespace App\Providers\Admin;

use App\Traits\Audit\LogsAudit;
use Illuminate\Support\Collection;

class CurrencyProvider
{
    use LogsAudit;

    public $currency;

    public function __construct($currency = null)
    {
        $this->currency = $currency;
    }

    public function set($currency)
    {
        $this->currency = $currency;

        $this->logAuditAction('currency_provider.set', [
            'payload' => [
                'keys' => $this->extractKeys($currency),
            ],
            'status' => 'success',
        ]);

        return $this->currency;
    }

    public function getData()
    {
        $data = $this->currency;

        $this->logAuditAction('currency_provider.get', [
            'result' => [
                'keys' => $this->extractKeys($data),
            ],
            'status' => 'success',
        ]);

        return $data;
    }

    public static function default()
    {
        return app(CurrencyProvider::class)->getData();
    }

    protected function extractKeys($value): array
    {
        if ($value instanceof Collection) {
            return $value->keys()->all();
        }

        if (is_array($value)) {
            return array_keys($value);
        }

        if (is_object($value)) {
            return array_keys(get_object_vars($value));
        }

        return [];
    }
}