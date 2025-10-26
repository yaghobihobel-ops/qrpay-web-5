<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Crypt;

class EncryptedJson implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        try {
            $decoded = Crypt::decryptString($value);
        } catch (DecryptException $e) {
            $decoded = $value;
        }

        $json = json_decode($decoded);

        return $json === null ? $decoded : $json;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_object($value)) {
            $value = json_decode(json_encode($value), true);
        }

        if (! is_string($value)) {
            $value = json_encode($value);
        }

        return Crypt::encryptString($value);
    }
}
