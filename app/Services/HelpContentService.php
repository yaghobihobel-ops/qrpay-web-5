<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class HelpContentService
{
    protected string $basePath;
    protected string $manifestCacheKey = 'help_content_manifest';

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? resource_path('docs/help');
    }

    public function manifest(): array
    {
        $path = $this->basePath . DIRECTORY_SEPARATOR . 'manifest.json';

        return Cache::remember($this->manifestCacheKey, now()->addMinutes(10), function () use ($path) {
            if (!File::exists($path)) {
                return [
                    'default_language' => 'en',
                    'sections' => [],
                ];
            }

            $content = File::get($path);
            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Unable to parse help manifest: ' . json_last_error_msg());
            }

            $decoded['default_language'] = $decoded['default_language'] ?? 'en';
            $decoded['sections'] = $decoded['sections'] ?? [];

            return $decoded;
        });
    }

    public function refreshManifestCache(): void
    {
        Cache::forget($this->manifestCacheKey);
    }

    public function getSections(?string $language = null): array
    {
        $manifest = $this->manifest();
        $defaultLanguage = $manifest['default_language'] ?? 'en';

        return collect($manifest['sections'])
            ->map(function (array $section) use ($language, $defaultLanguage) {
                $sectionDefault = $section['default_language'] ?? $defaultLanguage;
                $resolvedLanguage = $language ?? $sectionDefault;
                $latest = $this->resolveLatestVersion($section);

                return [
                    'id' => $section['id'],
                    'title' => $this->translateField($section['title'] ?? [], $resolvedLanguage, $sectionDefault),
                    'summary' => $this->translateField($section['summary'] ?? [], $resolvedLanguage, $sectionDefault),
                    'category' => $section['category'] ?? null,
                    'tags' => $section['tags'] ?? [],
                    'default_language' => $sectionDefault,
                    'available_languages' => array_keys($latest['languages'] ?? []),
                    'latest_version' => $latest['version'] ?? null,
                    'released_at' => $latest['released_at'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    public function getContent(string $sectionId, ?string $language = null, ?string $version = null): ?array
    {
        $manifest = $this->manifest();
        $defaultLanguage = $manifest['default_language'] ?? 'en';

        $section = collect($manifest['sections'])->firstWhere('id', $sectionId);

        if (!$section) {
            return null;
        }

        $sectionDefault = $section['default_language'] ?? $defaultLanguage;
        $targetLanguage = $language ?? $sectionDefault;

        $versionData = $this->resolveVersion($section, $version, $targetLanguage, $sectionDefault);

        if (!$versionData) {
            return null;
        }

        $languageRecord = $versionData['languages'][$targetLanguage] ?? null;

        if (!$languageRecord) {
            $fallbackLang = $sectionDefault;
            $languageRecord = $versionData['languages'][$fallbackLang] ?? null;
            $targetLanguage = $languageRecord ? $fallbackLang : $targetLanguage;
        }

        if (!$languageRecord || empty($languageRecord['path'])) {
            return null;
        }

        $filePath = $this->basePath . DIRECTORY_SEPARATOR . $languageRecord['path'];

        if (!File::exists($filePath)) {
            return null;
        }

        $raw = File::get($filePath);
        $html = method_exists(Str::class, 'markdown') ? Str::markdown($raw) : Str::of($raw)->markdown();

        $faqs = collect($versionData['faqs'] ?? [])->map(function (array $faq) use ($targetLanguage, $sectionDefault) {
            return [
                'id' => $faq['id'],
                'question' => $this->translateField($faq['question'] ?? [], $targetLanguage, $sectionDefault),
                'anchor' => $faq['anchor'] ?? null,
            ];
        })->values()->all();

        return [
            'section' => [
                'id' => $section['id'],
                'title' => $this->translateField($section['title'] ?? [], $targetLanguage, $sectionDefault),
                'summary' => $this->translateField($section['summary'] ?? [], $targetLanguage, $sectionDefault),
                'category' => $section['category'] ?? null,
                'tags' => $section['tags'] ?? [],
            ],
            'version' => $versionData['version'] ?? null,
            'language' => $targetLanguage,
            'released_at' => $versionData['released_at'] ?? null,
            'content' => $html,
            'raw' => $raw,
            'faqs' => $faqs,
        ];
    }

    public function resolveFaq(string $sectionId, string $faqId, ?string $language = null): ?array
    {
        $manifest = $this->manifest();
        $defaultLanguage = $manifest['default_language'] ?? 'en';
        $section = collect($manifest['sections'])->firstWhere('id', $sectionId);

        if (!$section) {
            return null;
        }

        $sectionDefault = $section['default_language'] ?? $defaultLanguage;
        $language = $language ?? $sectionDefault;

        foreach ($section['versions'] ?? [] as $version) {
            foreach ($version['faqs'] ?? [] as $faq) {
                if (($faq['id'] ?? null) === $faqId) {
                    return [
                        'id' => $faqId,
                        'question' => $this->translateField($faq['question'] ?? [], $language, $sectionDefault),
                        'version' => $version['version'] ?? null,
                        'anchor' => $faq['anchor'] ?? null,
                        'language' => $language,
                    ];
                }
            }
        }

        return null;
    }

    protected function translateField(array $translations, string $language, string $fallback): ?string
    {
        if (isset($translations[$language])) {
            return $translations[$language];
        }

        if (isset($translations[$fallback])) {
            return $translations[$fallback];
        }

        return $translations[array_key_first($translations)] ?? null;
    }

    protected function resolveVersion(array $section, ?string $version, string $language, string $fallback): ?array
    {
        $versions = $section['versions'] ?? [];

        if (!$versions) {
            return null;
        }

        if ($version) {
            foreach ($versions as $item) {
                if (($item['version'] ?? null) === $version) {
                    return $item;
                }
            }
        }

        usort($versions, function ($a, $b) {
            return version_compare($b['version'] ?? '0.0.0', $a['version'] ?? '0.0.0');
        });

        foreach ($versions as $item) {
            if (isset($item['languages'][$language])) {
                return $item;
            }
        }

        foreach ($versions as $item) {
            if (isset($item['languages'][$fallback])) {
                return $item;
            }
        }

        return $versions[0] ?? null;
    }

    protected function resolveLatestVersion(array $section): ?array
    {
        $versions = $section['versions'] ?? [];

        if (!$versions) {
            return null;
        }

        usort($versions, function ($a, $b) {
            return version_compare($b['version'] ?? '0.0.0', $a['version'] ?? '0.0.0');
        });

        return $versions[0] ?? null;
    }
}
