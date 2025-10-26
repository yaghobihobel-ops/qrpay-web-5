<?php

namespace App\Providers\Admin;

use App\Traits\Audit\LogsAudit;
use Illuminate\Support\Collection;

class BasicSettingsProvider
{
    use LogsAudit;

    public $setting;

    public function __construct($settings = null)
    {
        $this->setting = $settings;
    }

    public function set($settings)
    {
        $this->setting = $settings;

        $this->logAuditAction('basic_settings.set', [
            'payload' => [
                'keys' => $this->extractKeys($settings),
            ],
            'result' => [
                'keys' => $this->extractKeys($this->setting),
            ],
            'status' => 'success',
        ]);

        return $this->setting;
    }

    public function getData()
    {
        $data = $this->setting;

        $this->logAuditAction('basic_settings.get', [
            'result' => [
                'keys' => $this->extractKeys($data),
            ],
            'status' => 'success',
        ]);

        return $data;
    }

    public static function get()
    {
        return app(BasicSettingsProvider::class)->getData();
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