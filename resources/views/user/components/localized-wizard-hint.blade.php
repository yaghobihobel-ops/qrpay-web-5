@props([
    'context',
    'heading' => null,
    'description' => null,
    'defaultLocale' => app()->getLocale(),
    'dusk' => null,
])

<div
    class="localized-wizard-card"
    data-localized-wizard
    data-context="{{ $context }}"
    data-heading="{{ $heading ?? __('messaging.labels.localized_guidance') }}"
    data-description="{{ $description ?? '' }}"
    data-default-locale="{{ $defaultLocale }}"
    @if($dusk) dusk="{{ $dusk }}" @endif
>
    <noscript>
        <div class="localized-wizard__summary">{{ __('messaging.labels.fallback_notice') }}</div>
    </noscript>
</div>
