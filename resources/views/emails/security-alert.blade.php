@component('mail::message')
# {{ __('Security Alert for :domain', ['domain' => ucfirst($context->domain)]) }}

{{ __('The :domain service has encountered :count consecutive failures (threshold: :threshold).', [
    'domain' => $context->domain,
    'count' => $failureCount,
    'threshold' => $threshold,
]) }}

**{{ __('Operation') }}:** {{ $context->operation }}  
**{{ __('Provider') }}:** {{ $context->provider }}  
**{{ __('Correlation ID') }}:** {{ $context->correlationId }}  
**{{ __('Last error') }}:** {{ $exception->getMessage() }}

@component('mail::panel')
{{ __('Please investigate the service health and provider overrides immediately.') }}
@endcomponent

{{ __('Thank you,') }}<br>
{{ config('app.name') }}
@endcomponent
