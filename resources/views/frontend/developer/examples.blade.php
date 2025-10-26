@extends('frontend.layouts.developer_master')

@php
    $lang = selectedLang();
@endphp

@section('content')
<div class="developer-body-wrapper">
    <div class="developer-main-wrapper">
        <h1 class="heading-title mb-20">{{ __("Examples") }} </h1>
        <p class="pb-10">{{ __("For code examples and implementation guides, please refer to the “Examples” section on our developer portal.") }} <a href="https://github.com/appdevsx/QRPay-Gateway-Example.git" target="_blank" class="highlight text--base">{{ __("Go to GitHub Repository") }}</a></p>

        <div class="row gy-4">
            <div class="col-lg-6">
                <div class="p-4 rounded-3 shadow-sm bg-white h-100">
                    <h2 class="heading-title fs-4 mb-3">{{ __('messaging.labels.localized_guidance') }}</h2>
                    <p class="text-muted mb-3">{{ __('messaging.labels.localized_guidance_intro') }}</p>
                    @include('user.components.localized-wizard-hint', [
                        'context' => 'add-money',
                        'heading' => __('messaging.labels.localized_guidance'),
                        'description' => __('messaging.labels.instructions_heading'),
                        'defaultLocale' => app()->getLocale(),
                        'dusk' => 'developer-localized-wizard',
                    ])
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4 rounded-3 shadow-sm bg-white h-100">
                    <h2 class="heading-title fs-4 mb-3">{{ __('messaging.labels.scenario_playbook') }}</h2>
                    <p class="text-muted mb-3">{{ __('messaging.labels.scenario_intro') }}</p>
                    @include('user.components.scenario-explorer', [
                        'scenario' => 'qr',
                        'heading' => __('messaging.labels.qr_flow_heading'),
                        'defaultLocale' => app()->getLocale(),
                        'dusk' => 'developer-qr-scenario',
                    ])
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4 rounded-3 shadow-sm bg-white h-100">
                    <h2 class="heading-title fs-4 mb-3">{{ __('messaging.labels.alipay_flow_heading') }}</h2>
                    <p class="text-muted mb-3">{{ __('messaging.labels.scenario_intro') }}</p>
                    @include('user.components.scenario-explorer', [
                        'scenario' => 'alipay',
                        'heading' => __('messaging.labels.alipay_flow_heading'),
                        'defaultLocale' => app()->getLocale(),
                        'dusk' => 'developer-alipay-scenario',
                    ])
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4 rounded-3 shadow-sm bg-white h-100">
                    <h2 class="heading-title fs-4 mb-3">{{ __('messaging.labels.bank_flow_heading') }}</h2>
                    <p class="text-muted mb-3">{{ __('messaging.labels.scenario_intro') }}</p>
                    @include('user.components.scenario-explorer', [
                        'scenario' => 'bankAuth',
                        'heading' => __('messaging.labels.bank_flow_heading'),
                        'defaultLocale' => app()->getLocale(),
                        'dusk' => 'developer-bank-scenario',
                    ])
                </div>
            </div>
        </div>
    </div>
    <div class="page-change-area">
        <div class="navigation-wrapper">
            <a href="{{ setRoute('developer.best.practices') }}" class="left"><i class="las la-arrow-left me-1"></i> {{ __("Best Practices") }}</a>
            <a href=" {{ setRoute('developer.faq') }}" class="right">{{ __("FAQ") }} <i class="las la-arrow-right ms-1"></i></a>
        </div>
    </div>
</div>
@endsection


@push("script")

@endpush
