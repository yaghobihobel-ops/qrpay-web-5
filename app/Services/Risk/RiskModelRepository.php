<?php

namespace App\Services\Risk;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class RiskModelRepository
{
    public function __construct(private string $disk = 'local')
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getModel(string $name): array
    {
        return Cache::remember("risk-models-{$name}", 300, function () use ($name) {
            $path = "models/{$name}.json";

            try {
                $contents = Storage::disk($this->disk)->get($path);
            } catch (FileNotFoundException) {
                return [];
            }

            return json_decode($contents, true, 512, JSON_THROW_ON_ERROR) ?? [];
        });
    }
}
