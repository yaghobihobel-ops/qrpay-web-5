@props([
    'scenario',
    'heading' => null,
    'defaultLocale' => app()->getLocale(),
    'dusk' => null,
])

<div
    class="scenario-explorer-mount"
    data-scenario-explorer
    data-scenario="{{ $scenario }}"
    data-default-locale="{{ $defaultLocale }}"
    data-heading="{{ $heading ?? __('messaging.labels.scenario_playbook') }}"
    data-steps-label="{{ __('messaging.labels.steps_heading') }}"
    data-compliance-label="{{ __('messaging.labels.compliance_heading') }}"
    data-handoff-label="{{ __('messaging.labels.handoff_label') }}"
    data-fallback="{{ __('messaging.labels.scenario_fallback') }}"
    @if($dusk) dusk="{{ $dusk }}" @endif
>
    <noscript>
        <div class="scenario-explorer-summary">{{ __('messaging.labels.scenario_fallback') }}</div>
    </noscript>
</div>
