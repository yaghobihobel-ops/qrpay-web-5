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
        $gatewayOptions = collect($payment_gateways ?? [])->map(function ($item) {
            $rate = $item->rate ?? 1;
            $precision = optional($item->gateway)->crypto ? 8 : 2;
            $minBase = $rate > 0 ? getAmount($item->min_limit / $rate, $precision) : 0;
            $maxBase = $rate > 0 ? getAmount($item->max_limit / $rate, $precision) : 0;
            return [
                'value' => $item->alias,
                'label' => $item->name . ' · ' . $item->currency_code,
                'meta' => [
                    'currency' => $item->currency_code,
                    'min' => $minBase,
                    'max' => $maxBase,
                    'fixedCharge' => $item->fixed_charge,
                    'percentCharge' => $item->percent_charge,
                    'rate' => $rate,
                    'isCrypto' => (bool) optional($item->gateway)->crypto,
                ],
            ];
        })->values();
        $wizardConfig = [
            'flow' => __('Withdraw'),
            'title' => __(@$page_title),
            'subtitle' => __('Step through gateway selection, amount entry, and instant fee calculation.'),
            'formId' => 'withdraw-wizard',
            'csrfToken' => csrf_token(),
            'currency' => get_default_currency_code(),
            'locale' => app()->getLocale(),
            'startingValues' => [
                'amount' => old('amount'),
                'gateway' => old('gateway'),
            ],
            'meta' => [
                'previewTitle' => __('Withdraw preview'),
                'helper' => __('All limits are shown in your default currency.'),
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
                    'title' => __('Choose gateway'),
                    'description' => __('Select where funds should be transferred'),
                    'fields' => [
                        [
                            'name' => 'gateway',
                            'type' => 'select',
                            'label' => __('Payment gateway'),
                            'placeholder' => __('Select a gateway'),
                            'required' => true,
                            'options' => $gatewayOptions,
                            'helper' => __('Supported gateways are filtered to your enabled channels.'),
                        ],
                    ],
                ],
                [
                    'title' => __('Withdraw amount'),
                    'description' => __('Enter how much you want to move'),
                    'fields' => [
                        [
                            'name' => 'amount',
                            'type' => 'number',
                            'label' => __('Amount'),
                            'placeholder' => __('enter Amount'),
                            'required' => true,
                            'helper' => __('Preview updates in real time with gateway fees.'),
                        ],
                    ],
                ],
                [
                    'title' => __('Review & submit'),
                    'description' => __('Confirm the conversion rate before sending'),
                    'fields' => [],
                ],
            ],
        ];
    @endphp
    <div class="mt-4" data-flow-wizard='@json($wizardConfig)'></div>
    <form id="withdraw-wizard" action="{{ setRoute('user.money.out.insert') }}" method="POST" class="d-none">
        @csrf
    </form>

    <div class="dashboard-list-area mt-20">
        <div class="dashboard-header-wrapper">
            <h4 class="title ">{{__("withdraw Log")}}</h4>
            <div class="dashboard-btn-wrapper">
                <div class="dashboard-btn mb-2">
                    <a href="{{ setRoute('user.transactions.index','withdraw') }}" class="btn--base">{{__("View More")}}</a>
                </div>
            </div>
        </div>
        <div class="dashboard-list-wrapper">
            @include('user.components.transaction-log',compact("transactions"))
        </div>
    </div>
</div>
@endsection
