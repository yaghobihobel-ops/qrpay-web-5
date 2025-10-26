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
        $minLimit = getAmount(($sendMoneyCharge->min_limit ?? 0) * get_default_currency_rate(), 2);
        $maxLimit = getAmount(($sendMoneyCharge->max_limit ?? 0) * get_default_currency_rate(), 2);
        $wizardConfig = [
            'flow' => __('Exchange'),
            'title' => __(@$page_title),
            'subtitle' => __('Guide teammates through exchanging money between users with inline validation.'),
            'formId' => 'send-money-wizard',
            'csrfToken' => csrf_token(),
            'currency' => get_default_currency_code(),
            'locale' => app()->getLocale(),
            'startingValues' => [
                'email' => old('email'),
                'amount' => old('amount'),
            ],
            'meta' => [
                'previewTitle' => __('Send preview'),
                'helper' => __('Fees and recipient amount update automatically.'),
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
                    'title' => __('Recipient details'),
                    'description' => __('Confirm who should receive the funds'),
                    'fields' => [
                        [
                            'name' => 'email',
                            'type' => 'email',
                            'label' => __('Recipient email'),
                            'placeholder' => __('enter Email Address'),
                            'required' => true,
                            'helper' => __('We will detect if the user is eligible before submitting.'),
                        ],
                    ],
                ],
                [
                    'title' => __('Send amount'),
                    'description' => __('Define the amount you want to exchange'),
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
                            'helper' => __('Respect the dynamic limits for your account.'),
                        ],
                    ],
                ],
                [
                    'title' => __('Review & submit'),
                    'description' => __('Confirm the payable amount and recipient share'),
                    'fields' => [],
                ],
            ],
        ];
    @endphp
    <div class="mt-4" data-flow-wizard='@json($wizardConfig)'></div>
    <form id="send-money-wizard" action="{{ setRoute('user.send.money.confirmed') }}" method="POST" class="d-none">
        @csrf
    </form>

    <div class="dashboard-list-area mt-20">
        <div class="dashboard-header-wrapper">
            <h4 class="title ">{{__("Send Money Log")}}</h4>
            <div class="dashboard-btn-wrapper">
                <div class="dashboard-btn mb-2">
                    <a href="{{ setRoute('user.transactions.index','transfer-money') }}" class="btn--base">{{__("View More")}}</a>
                </div>
            </div>
        </div>
        <div class="dashboard-list-wrapper">
            @include('user.components.transaction-log',compact("transactions"))
        </div>
    </div>
</div>
@endsection
