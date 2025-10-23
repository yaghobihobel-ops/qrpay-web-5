@extends('user.layouts.master')

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __(@$page_title)])
@endsection

@section('content')
<div class="body-wrapper">
    @php
        $minLimit = getAmount(($makePaymentCharge->min_limit ?? 0) * get_default_currency_rate(), 2);
        $maxLimit = getAmount(($makePaymentCharge->max_limit ?? 0) * get_default_currency_rate(), 2);
        $wizardConfig = [
            'flow' => __('Payment'),
            'title' => __(@$page_title),
            'subtitle' => __('Send payments to verified merchants with real-time validation.'),
            'formId' => 'make-payment-wizard',
            'csrfToken' => csrf_token(),
            'currency' => get_default_currency_code(),
            'locale' => app()->getLocale(),
            'startingValues' => [
                'email' => old('email'),
                'amount' => old('amount'),
            ],
            'meta' => [
                'previewTitle' => __('Live preview'),
                'helper' => __('Charges update automatically while you type.'),
                'labels' => [
                    'amount' => __('Amount'),
                    'conversion' => __('Conversion'),
                    'fees' => __('Fees & Charges'),
                    'willGet' => __('Recipient will get'),
                    'total' => __('Total Payable'),
                    'limit' => __('Limit'),
                    'rate' => __('Rate'),
                ],
            ],
            'steps' => [
                [
                    'title' => __('Merchant details'),
                    'description' => __('Confirm who you are paying'),
                    'fields' => [
                        [
                            'name' => 'email',
                            'type' => 'email',
                            'label' => __('Merchant email'),
                            'placeholder' => __('enter Email Address'),
                            'required' => true,
                            'helper' => __('We will verify the merchant automatically.'),
                        ],
                    ],
                ],
                [
                    'title' => __('Payment amount'),
                    'description' => __('Choose how much you want to pay'),
                    'fields' => [
                        [
                            'name' => 'amount',
                            'type' => 'number',
                            'label' => __('Amount'),
                            'placeholder' => __('enter Amount'),
                            'required' => true,
                            'rules' => [
                                'min' => $minLimit,
                                'max' => $maxLimit,
                            ],
                            'helper' => __('Limits depend on your verification tier.'),
                        ],
                    ],
                ],
                [
                    'title' => __('Review & submit'),
                    'description' => __('Double-check fees before confirming'),
                    'fields' => [],
                ],
            ],
        ];
    @endphp
    <div class="mt-4" data-flow-wizard='@json($wizardConfig)'></div>
    <form id="make-payment-wizard" action="{{ setRoute('user.make.payment.confirmed') }}" method="POST" class="d-none">
        @csrf
    </form>

    <div class="dashboard-list-area mt-20">
        <div class="dashboard-header-wrapper">
            <h4 class="title ">{{__("Make Payment Log")}}</h4>
            <div class="dashboard-btn-wrapper">
                <div class="dashboard-btn mb-2">
                    <a href="{{ setRoute('user.transactions.index','make-payment') }}" class="btn--base">{{__("View More")}}</a>
                </div>
            </div>
        </div>
        <div class="dashboard-list-wrapper">
            @include('user.components.transaction-log',compact("transactions"))
        </div>
    </div>
</div>
@endsection
