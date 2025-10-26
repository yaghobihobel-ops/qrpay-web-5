<?php

namespace App\Services\Notifications;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class LocalizedMessagingService
{
    protected array $config;

    protected string $fallbackLocale;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? Config::get('messaging', []);
        $this->fallbackLocale = $this->config['fallback_locale'] ?? config('app.fallback_locale', 'en');
    }

    public function transform(string $channel, array $payload, array $context = []): array
    {
        $country = $this->normalizeCountry($context['country'] ?? null);
        $locale = $this->resolveLocale($context['locale'] ?? null, $country);

        $placeholders = $payload['placeholders'] ?? [];
        $fallbackTitle = $payload['title'] ?? Arr::get($payload, 'fallback_title', '');
        $fallbackBody = $payload['desc'] ?? Arr::get($payload, 'body', '');

        if (isset($payload['template'])) {
            $templateKey = $payload['template'];
            $title = $this->translateFromTemplate($channel, $templateKey . '.title', $placeholders, $locale, $fallbackTitle);
            $body = $this->translateFromTemplate($channel, $templateKey . '.body', $placeholders, $locale, $fallbackBody);
        } else {
            $title = $fallbackTitle;
            $body = $fallbackBody;
        }

        $message = [
            'title' => $title,
            'body' => $body,
        ];

        $message = $this->applyCountryRules($channel, $country, $message);

        return array_merge($payload, [
            'title' => $message['title'],
            'desc' => $message['body'],
            'body' => $message['body'],
            'meta' => $message['meta'] ?? [],
            'locale' => $locale,
            'country' => $country,
        ]);
    }

    public function emailTemplate(string $key, array $placeholders, array $context = [], array $fallback = []): array
    {
        $country = $this->normalizeCountry($context['country'] ?? null);
        $locale = $this->resolveLocale($context['locale'] ?? null, $country);

        $subject = $this->translateFromTemplate('email', $key . '.subject', $placeholders, $locale, $fallback['subject'] ?? '');
        $intro = $this->translateFromTemplate('email', $key . '.intro', $placeholders, $locale, $fallback['intro'] ?? '');
        $footer = $this->translateFromTemplate('email', $key . '.footer', $placeholders, $locale, $fallback['footer'] ?? '');

        $rules = Arr::get($this->config, "country_rules.$country.email", []);
        if (!$footer && isset($rules['footer'])) {
            $footer = $rules['footer'];
        }

        return [
            'subject' => $subject,
            'intro' => $intro,
            'footer' => $footer,
            'locale' => $locale,
            'country' => $country,
            'meta' => Arr::except($rules, ['footer']),
        ];
    }

    public function smsTemplate(string $key, array $placeholders, array $context = [], string $fallback = ''): array
    {
        $country = $this->normalizeCountry($context['country'] ?? null);
        $locale = $this->resolveLocale($context['locale'] ?? null, $country);

        $message = $this->translateFromTemplate('sms', $key, $placeholders, $locale, $fallback);

        $rules = Arr::get($this->config, "country_rules.$country.sms", []);
        if (isset($rules['prefix'])) {
            $message = trim($rules['prefix']) . ' ' . $message;
        }
        if (isset($rules['suffix'])) {
            $message = rtrim($message, '.') . ' ' . trim($rules['suffix']);
        }
        if (isset($rules['signature'])) {
            $message .= ' ' . trim($rules['signature']);
        }

        return [
            'message' => $message,
            'locale' => $locale,
            'country' => $country,
            'meta' => Arr::except($rules, ['prefix', 'suffix', 'signature']),
        ];
    }

    public function resolveUserContext(mixed $user, array $overrides = []): array
    {
        $country = $this->normalizeCountry($overrides['country'] ?? $this->extractCountryFromUser($user));
        $locale = $this->resolveLocale($overrides['locale'] ?? null, $country);

        return [
            'country' => $country,
            'locale' => $locale,
        ];
    }

    protected function resolveLocale(?string $preferredLocale, ?string $country): string
    {
        if ($preferredLocale) {
            return $preferredLocale;
        }

        $country = strtoupper((string) $country);
        $map = $this->config['locale_map'] ?? [];

        if ($country && isset($map[$country])) {
            return $map[$country];
        }

        return $map['DEFAULT'] ?? $this->fallbackLocale;
    }

    protected function applyCountryRules(string $channel, ?string $country, array $message): array
    {
        $rules = Arr::get($this->config, "country_rules.$country.$channel", []);
        $meta = Arr::except($rules, ['prefix', 'suffix', 'max_length', 'signature']);

        if (isset($rules['prefix'])) {
            $message['title'] = trim($rules['prefix']) . ' ' . $message['title'];
        }

        if (isset($rules['suffix'])) {
            $message['body'] = rtrim($message['body']) . ' ' . trim($rules['suffix']);
        }

        if (isset($rules['signature']) && $channel === 'sms') {
            $message['body'] = rtrim($message['body']) . ' ' . trim($rules['signature']);
        }

        if (isset($rules['max_length'])) {
            $message['body'] = Str::limit($message['body'], (int) $rules['max_length']);
        }

        $message['meta'] = $meta;

        return $message;
    }

    protected function translateFromTemplate(string $channel, string $key, array $placeholders, string $locale, string $fallback = ''): string
    {
        $fullKey = "messaging.$channel.$key";
        $translation = Lang::get($fullKey, $placeholders, $locale);
        if ($translation === $fullKey) {
            $translation = Lang::get($fullKey, $placeholders, $this->fallbackLocale);
        }

        return $translation === $fullKey ? $fallback : $translation;
    }

    protected function normalizeCountry(?string $country): ?string
    {
        if (!$country) {
            return null;
        }

        $country = strtoupper(preg_replace('/[^A-Z]/', '', $country));
        $aliases = $this->config['country_aliases'] ?? [];

        return $aliases[$country] ?? ($country ?: null);
    }

    protected function extractCountryFromUser(mixed $user): ?string
    {
        if (!$user) {
            return null;
        }

        if (isset($user->country) && $user->country) {
            return $this->normalizeCountry($user->country);
        }

        if (isset($user->address) && $user->address) {
            $address = $user->address;

            if (is_string($address)) {
                $decoded = json_decode($address, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $address = $decoded;
                }
            }

            if (is_array($address)) {
                $country = $address['country_code'] ?? $address['country'] ?? null;
                if ($country) {
                    return $this->normalizeCountry($country);
                }
            }

            if (is_object($address)) {
                $country = $address->country_code ?? $address->country ?? null;
                if ($country) {
                    return $this->normalizeCountry($country);
                }
            }
        }

        return null;
    }
}
