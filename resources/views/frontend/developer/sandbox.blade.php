@extends('frontend.layouts.developer_master')

@section('content')
<div class="developer-body-wrapper">
    <div class="developer-main-wrapper">
        <h1 class="heading-title mb-20">{{ __("Sandbox Environment") }}</h1>
        <p>{{ __("Use the dedicated sandbox to validate your integration without affecting live settlements.") }}</p>
        <div class="mt-20">
            <h3 class="heading-title h5">{{ __("Endpoints") }}</h3>
            <ul class="unordered-list mt-10">
                <li><code>https://sandbox-api.qrpay.test</code> — {{ __("API base URL") }}</li>
                <li><code>https://sandbox-dashboard.qrpay.test</code> — {{ __("Merchant dashboard") }}</li>
            </ul>
        </div>
        <div class="mt-20">
            <h3 class="heading-title h5">{{ __("Sandbox credentials") }}</h3>
            <p>{{ __("Request sandbox access from the admin panel (Support → Developer Sandbox). Credentials are automatically rotated every 30 days.") }}</p>
        </div>
        <div class="mt-20">
            <h3 class="heading-title h5">{{ __("Test cards & wallets") }}</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __("Method") }}</th>
                        <th>{{ __("Details") }}</th>
                        <th>{{ __("Expected Result") }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ __("Visa (success)") }}</td>
                        <td>4111 1111 1111 1111 — {{ __("Any future expiry, CVC 123") }}</td>
                        <td>{{ __("Payment succeeds with status `succeeded`") }}</td>
                    </tr>
                    <tr>
                        <td>{{ __("Visa (insufficient funds)") }}</td>
                        <td>4000 0000 0000 9995 — {{ __("Any future expiry, CVC 123") }}</td>
                        <td>{{ __("Returns error code `insufficient_funds`") }}</td>
                    </tr>
                    <tr>
                        <td>{{ __("Wallet QR") }}</td>
                        <td>{{ __("Scan the generated QR code in the QRPay Sandbox mobile app") }}</td>
                        <td>{{ __("Updates the payment to `succeeded` after confirmation") }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="alert alert-info mt-30">
            <strong>{{ __("Heads up:") }}</strong> {{ __("Sandbox data resets every Sunday at 00:00 UTC. Export any reports beforehand.") }}
        </div>
    </div>
    <div class="page-change-area">
        <div class="navigation-wrapper">
            <a href="{{ setRoute('developer.quickstart') }}" class="left"><i class="las la-arrow-left me-1"></i> {{ __("Quick Start") }}</a>
            <a href="{{ setRoute('developer.openapi') }}" class="right">{{ __("OpenAPI & SDKs") }} <i class="las la-arrow-right ms-1"></i></a>
        </div>
    </div>
</div>
@endsection

@push('script')
@endpush
