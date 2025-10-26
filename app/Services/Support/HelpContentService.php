<?php

namespace App\Services\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use InvalidArgumentException;

class HelpContentService
{
    protected string $docsPath;

    public function __construct(?string $docsPath = null)
    {
        $this->docsPath = $docsPath ?? resource_path('docs');
    }

    public function getContent(string $section): array
    {
        $normalizedSection = $this->normalizeSection($section);

        $translations = Lang::get("help.sections.$normalizedSection");
        if (!is_array($translations) || empty($translations)) {
            throw new InvalidArgumentException("Help section [$section] is not defined.");
        }

        $markdownPath = $this->docsPath . DIRECTORY_SEPARATOR . $normalizedSection . '.md';
        if (!File::exists($markdownPath)) {
            throw new InvalidArgumentException("Help content file [$markdownPath] does not exist.");
        }

        $rawContent = File::get($markdownPath);

        return [
            'section' => $normalizedSection,
            'title' => $translations['title'] ?? Str::headline($normalizedSection),
            'summary' => $translations['summary'] ?? null,
            'video' => $translations['video'] ?? null,
            'content' => Str::markdown($rawContent),
        ];
    }

    protected function normalizeSection(string $section): string
    {
        return Str::slug($section);
    }
}
