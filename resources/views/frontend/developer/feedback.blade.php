@extends('frontend.layouts.developer_master')

@section('content')
<div class="developer-body-wrapper">
    <div class="developer-main-wrapper">
        <h1 class="heading-title mb-20">{{ __("Feedback & Changelog") }}</h1>
        <p>{{ __("Share ideas, report bugs, and stay informed about API changes.") }}</p>
        <div class="mt-20">
            <h3 class="heading-title h5">{{ __("Issue tracker") }}</h3>
            <p>{{ __("Log defects or integration blockers in the Developer Issue Tracker. The product team triages submissions daily.") }}</p>
            <a class="btn--base mt-10" href="https://github.com/qrpay/qrpay-web/issues/new/choose" target="_blank" rel="noopener">{{ __("Submit an issue") }}</a>
        </div>
        <div class="mt-30">
            <h3 class="heading-title h5">{{ __("Feature requests") }}</h3>
            <p>{{ __("Vote on roadmap candidates or propose new endpoints in the Feature Request board.") }}</p>
            <a class="btn--base mt-10" href="https://github.com/orgs/qrpay/discussions/new?category=ideas" target="_blank" rel="noopener">{{ __("Request a feature") }}</a>
        </div>
        <div class="mt-30">
            <h3 class="heading-title h5">{{ __("Release cadence") }}</h3>
            <p>{{ __("QRPay ships a stable API release on the first Tuesday of every month. Release candidates are available one week earlier in the sandbox environment.") }}</p>
            <p>{{ __("Track upcoming deprecations and schema updates in the official changelog below.") }}</p>
            <div class="card mt-15">
                <div class="card-body">
                    <h4 class="heading-title h6">{{ __("API Changelog") }}</h4>
                    <ul class="unordered-list mt-10">
                        <li><strong>2024-04-02</strong> — {{ __("v1.0.0 launched with payments and token endpoints.") }}</li>
                        <li><strong>2024-03-15</strong> — {{ __("Sandbox refresh with automated credential rotation.") }}</li>
                    </ul>
                    <a class="text-decoration-underline d-inline-block mt-15" href="https://github.com/qrpay/qrpay-web/releases" target="_blank" rel="noopener">{{ __("View full changelog") }}</a>
                </div>
            </div>
        </div>
    </div>
    <div class="page-change-area">
        <div class="navigation-wrapper">
            <a href="{{ setRoute('developer.postman') }}" class="left"><i class="las la-arrow-left me-1"></i> {{ __("Postman Collection") }}</a>
            <a href="{{ setRoute('developer.support') }}" class="right">{{ __("Support") }} <i class="las la-arrow-right ms-1"></i></a>
        </div>
    </div>
</div>
@endsection

@push('script')
@endpush
