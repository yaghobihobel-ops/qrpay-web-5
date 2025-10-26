<?php

namespace App\Providers\Admin;

use App\Traits\Audit\LogsAudit;
use Illuminate\Support\Collection;

class ExtensionProvider
{
    use LogsAudit;

    public $extension;

    public function __construct($extensions = null)
    {
        $this->extension = $extensions;
    }

    public function set($extensions)
    {
        $this->extension = $extensions;

        $this->logAuditAction('extension_provider.set', [
            'payload' => [
                'keys' => $this->extractKeys($extensions),
            ],
            'status' => 'success',
        ]);

        return $this->extension;
    }

    public function getData()
    {
        $data = $this->extension;

        $this->logAuditAction('extension_provider.get', [
            'result' => [
                'keys' => $this->extractKeys($data),
            ],
            'status' => 'success',
        ]);

        return $data;
    }

    public static function get()
    {
        return app(ExtensionProvider::class)->getData();
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