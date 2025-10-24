@extends('frontend.layouts.developer_master')

@section('content')
<div class="developer-body-wrapper">
    <div class="developer-main-wrapper">
        <h1 class="heading-title mb-20">{{ __("Postman Collection") }}</h1>
        <p>{{ __("Import the curated Postman collection to explore the QRPay API without writing any code.") }}</p>
        <div class="card mt-20">
            <div class="card-body">
                <h3 class="heading-title h5 mb-15">{{ __("Get started") }}</h3>
                <ol class="ordered-list">
                    <li>{{ __("Download the collection JSON") }} <a class="text-decoration-underline" href="{{ asset('docs/postman/qrpay-api.postman_collection.json') }}" download>{{ __("Download") }}</a></li>
                    <li>{{ __("Open Postman and choose Import → File") }}</li>
                    <li>{{ __("Set environment variables `baseUrl`, `clientId`, `clientSecret`, and `accessToken`.") }}</li>
                    <li>{{ __("Send the requests in order: Generate Access Token → Create Payment → Get Payment Status.") }}</li>
                </ol>
            </div>
        </div>
        <div class="mt-30">
            <h3 class="heading-title h5">{{ __("Automation tips") }}</h3>
            <p>{{ __("Add the collection to your CI smoke tests using Newman: `newman run qrpay-api.postman_collection.json --env-var baseUrl=https://sandbox-api.qrpay.test`.") }}</p>
        </div>
    </div>
    <div class="page-change-area">
        <div class="navigation-wrapper">
            <a href="{{ setRoute('developer.openapi') }}" class="left"><i class="las la-arrow-left me-1"></i> {{ __("OpenAPI & SDKs") }}</a>
            <a href="{{ setRoute('developer.feedback') }}" class="right">{{ __("Feedback & Changelog") }} <i class="las la-arrow-right ms-1"></i></a>
        </div>
    </div>
</div>
@endsection

@push('script')
@endpush
