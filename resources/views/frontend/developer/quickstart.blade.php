@extends('frontend.layouts.developer_master')

@section('content')
<div class="developer-body-wrapper">
    <div class="developer-main-wrapper">
        <h1 class="heading-title mb-20">{{ __("Quick Start") }}</h1>
        <p>{{ __("Follow these steps to make your first payment in minutes using the QRPay API and SDKs.") }}</p>
        <ol class="ordered-list mt-20">
            <li>{{ __("Create a merchant app in the admin portal and copy the client ID and client secret.") }}</li>
            <li>{{ __("Install the SDK for your preferred language from GitHub Packages (see below).") }}</li>
            <li>{{ __("Use the sandbox base URL `https://sandbox-api.qrpay.test` to authenticate and create payments while testing.") }}</li>
            <li>{{ __("Switch to production by updating the base URL and rotating credentials after go-live.") }}</li>
        </ol>
        <div class="card mt-30">
            <div class="card-body">
                <h3 class="heading-title mb-15 h5">{{ __("Sample: Initiate a payment") }}</h3>
                <div class="code-sample-grid">
                    <div>
                        <p class="fw-semibold">TypeScript (Node)</p>
                        <pre><code class="language-ts">import { Configuration, PaymentsApi } from '@qrpay/sdk';

const config = new Configuration({
  basePath: 'https://sandbox-api.qrpay.test',
  accessToken: process.env.QRPAY_ACCESS_TOKEN,
});

const payments = new PaymentsApi(config);
const response = await payments.createPayment({
  createPaymentRequest: {
    amount: 99.5,
    currency: 'USD',
    referenceId: 'INV-48291',
    customer: { name: 'Jane Doe', email: 'jane@example.com' },
  },
});
console.log(response.data.status);
</code></pre>
                    </div>
                    <div>
                        <p class="fw-semibold">Python</p>
                        <pre><code class="language-py">import os
from qrpay_sdk import PaymentsApi, Configuration, ApiClient

configuration = Configuration(
    host="https://sandbox-api.qrpay.test",
    access_token=os.environ["QRPAY_ACCESS_TOKEN"],
)

with ApiClient(configuration) as api_client:
    api = PaymentsApi(api_client)
    payment = api.create_payment({
        "amount": 99.5,
        "currency": "USD",
        "referenceId": "INV-48291",
        "customer": {"name": "Jane Doe", "email": "jane@example.com"},
    })
    print(payment.status)
</code></pre>
                    </div>
                    <div>
                        <p class="fw-semibold">PHP</p>
                        <pre><code class="language-php"><?php
use QRPay\Sdk\Configuration;
use QRPay\Sdk\Api\PaymentsApi;

$configuration = (new Configuration())
    ->setHost('https://sandbox-api.qrpay.test')
    ->setAccessToken(getenv('QRPAY_ACCESS_TOKEN'));

$api = new PaymentsApi(null, $configuration);
$payment = $api->createPayment([
    'amount' => 99.5,
    'currency' => 'USD',
    'referenceId' => 'INV-48291',
    'customer' => ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
]);

echo $payment->getStatus();
</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-change-area">
        <div class="navigation-wrapper">
            <a href="{{ setRoute('developer.index') }}" class="left"><i class="las la-arrow-left me-1"></i> {{ __("Introduction") }}</a>
            <a href="{{ setRoute('developer.sandbox') }}" class="right">{{ __("Sandbox Environment") }} <i class="las la-arrow-right ms-1"></i></a>
        </div>
    </div>
</div>
@endsection

@push('script')
@endpush

