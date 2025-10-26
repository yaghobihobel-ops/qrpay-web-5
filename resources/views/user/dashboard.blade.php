@extends('user.layouts.master')

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Dashboard")])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="dashboard-area mt-10">
        <div class="dashboard-header-wrapper">
            <h3 class="title">{{ __("Overview") }}</h3>
        </div>
        <div class="dashboard-item-area">
            <div class="row mb-20-none">
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{__("balance")}}</span>
                            <h3 class="title">{{ authWalletBalance() }} <span class="text--base">{{ @$baseCurrency->code }}</span></h3>
                        </div>

                        <div class="dashboard-icon">
                            <img src="{{  @$baseCurrency->currencyImage }}" alt="flag" />
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{__("Total Receive Remittance")}}</span>
                            <h3 class="title">{{ getAmount($data['totalReceiveRemittance'],2) }} <span class="text--base">{{ @$baseCurrency->code }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-receipt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __("Total Send Remittance") }}</span>
                            <h3 class="title">{{ getAmount($data['totalSendRemittance'],2) }} <span class="text--base">{{ @$baseCurrency->code }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __("Virtual Card") }}</span>
                            <h3 class="title">{{ getAmount($data['cardAmount'],2) }} <span class="text--base">{{ @$baseCurrency->code }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{__("Total Bill Pay")}}</span>
                            <h3 class="title">{{ getAmount($data['billPay'],2) }} <span class="text--base">{{ @$baseCurrency->code }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __("Total Mobile TopUp") }}</span>
                            <h3 class="title">{{ getAmount($data['topUps'],2) }} <span class="text--base">{{ @$baseCurrency->code }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-mobile"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __("Total Withdraw") }}</span>
                            <h3 class="title">{{ getAmount($data['withdraw'],2) }} <span class="text--base">{{ @$baseCurrency->code }}</span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __("Total Transactions") }}</span>
                            <h3 class="title">{{$data['total_transaction'] }} <span class="text--base"></span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-arrows-alt-h"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-20">
                    <div class="dashbord-item">
                        <div class="dashboard-content">
                            <span class="sub-title">{{ __("Total Gift Cards") }}</span>
                            <h3 class="title">{{$data['total_gift_cards'] }} <span class="text--base"></span></h3>
                        </div>
                        <div class="dashboard-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(!empty($loyaltySummary))
        <div class="loyalty-area mt-20">
            <div class="dashboard-header-wrapper">
                <h4 class="title">{{ __("Loyalty & Rewards") }}</h4>
                <span class="badge badge--base text-uppercase">{{ data_get($loyaltySummary, 'tier.label') }}</span>
            </div>
            <div class="row mb-20-none">
                <div class="col-xxl-4 col-xl-4 col-lg-12 mb-20">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">{{ __("Points Overview") }}</h5>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="fs-14 text-muted mb-1">{{ __("Available Points") }}</p>
                                    <h3 class="mb-0">{{ number_format(data_get($loyaltySummary, 'balance', 0)) }}</h3>
                                </div>
                                <div class="text-end">
                                    <p class="fs-14 text-muted mb-1">{{ __("Lifetime Points") }}</p>
                                    <h5 class="mb-0">{{ number_format(data_get($loyaltySummary, 'lifetime', 0)) }}</h5>
                                </div>
                            </div>
                            @if(data_get($loyaltySummary, 'next_tier'))
                                <div class="mt-4">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="text-muted">{{ __("Progress to next tier") }}</span>
                                        <span class="fw-bold">{{ data_get($loyaltySummary, 'progress_to_next', 0) }}%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg--base" role="progressbar" style="width: {{ data_get($loyaltySummary, 'progress_to_next', 0) }}%;" aria-valuenow="{{ data_get($loyaltySummary, 'progress_to_next', 0) }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <p class="fs-13 text-muted mt-2 mb-0">{{ __("Only :points pts away from :tier", ['points' => number_format(data_get($loyaltySummary, 'points_to_next', 0)), 'tier' => data_get($loyaltySummary, 'next_tier.label')]) }}</p>
                                </div>
                            @else
                                <p class="fs-13 text-muted mt-3 mb-0">{{ __("You unlocked the top tier! Enjoy all premium rewards.") }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-4 col-lg-6 mb-20">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">{{ __("Next Best Actions") }}</h5>
                            <ul class="list-group list-group-flush">
                                @forelse(data_get($loyaltySummary, 'suggested_actions', []) as $action)
                                    <li class="list-group-item px-0">{{ $action }}</li>
                                @empty
                                    <li class="list-group-item px-0 text-muted">{{ __("No actions required right now.") }}</li>
                                @endforelse
                            </ul>
                            <div class="mt-4">
                                <h6 class="mb-2">{{ __("Recent Activity") }}</h6>
                                <ul class="list-unstyled mb-0">
                                    @forelse(data_get($loyaltySummary, 'recent_events', []) as $event)
                                        <li class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold text-capitalize">{{ str_replace('_', ' ', data_get($event, 'event_type')) }}</span>
                                                <span class="text--base">{{ data_get($event, 'points_change') }}</span>
                                            </div>
                                            <small class="text-muted">{{ data_get($event, 'occurred_at')?->diffForHumans() }}</small>
                                        </li>
                                    @empty
                                        <li class="text-muted">{{ __("No reward history yet.") }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-xl-4 col-lg-6 mb-20">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">{{ __("Special Offers & Campaigns") }}</h5>
                            <div class="mb-3">
                                <h6 class="text-uppercase text-muted fs-12">{{ __("Featured Offers") }}</h6>
                                <ul class="list-unstyled mb-0">
                                    @forelse($specialOffers as $offer)
                                        <li class="mb-3">
                                            <div class="fw-bold">{{ $offer->name }}</div>
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($offer->message_template, 120) }}</small>
                                        </li>
                                    @empty
                                        <li class="text-muted">{{ __("No featured offers available right now.") }}</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div>
                                <h6 class="text-uppercase text-muted fs-12">{{ __("Recommended For You") }}</h6>
                                <ul class="list-unstyled mb-0">
                                    @forelse($campaignRecommendations as $campaign)
                                        <li class="mb-3">
                                            <div class="fw-bold">{{ $campaign->name }}</div>
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit($campaign->message_template, 100) }}</small>
                                        </li>
                                    @empty
                                        <li class="text-muted">{{ __("We will surface new campaigns as they launch.") }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="chart-area mt-30">
        <div class="row mb-20-none">
            <div class="col-xxl-7 col-xl-7 col-lg-7 mb-20">
                <div class="chart-wrapper">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ __("Add Money Chart") }}</h4>
                    </div>
                    <div class="chart-container">
                        <div id="chart1" data-chart_one_data="{{ json_encode($chartData['chart_one_data']) }}" data-month_day="{{ json_encode($chartData['month_day']) }}" class="chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-5 col-xl-5 col-lg-5 mb-20">
                <div class="chart-wrapper">
                    <div class="dashboard-header-wrapper">
                        <h4 class="title">{{ __("Withdraw Money") }}</h4>
                    </div>
                    <div class="chart-container">
                        <div id="chart3" data-chart_three_data="{{ json_encode($chartData['chart_two_data']) }}" data-month_day="{{ json_encode($chartData['month_day']) }}" class="chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="dashboard-list-area mt-20">
        <div class="dashboard-header-wrapper">
            <h4 class="title">{{ __("Latest Transactions") }}</h4>
            <div class="dashboard-btn-wrapper">
                <div class="dashboard-btn mb-2">
                    <a href="{{ setRoute('user.transactions.index') }}" class="btn--base">{{__("View More")}}</a>
                </div>
            </div>
        </div>
        <div class="dashboard-list-wrapper">
            @include('user.components.transaction-log',compact("transactions"))
        </div>
    </div>
</div>
@endsection
@push('script')
<script>
    var chart1 = $('#chart1');
    var chart_one_data = chart1.data('chart_one_data');
    var month_day = chart1.data('month_day');
    var options = {
        series: [
            {
            name: "{{ __('Pending') }}",
            color: "#0C56DB",
            data: chart_one_data.pending_data
            }, {
            name: "{{ __('Completed') }}",
            color: "rgba(0, 227, 150, 0.85)",
            data: chart_one_data.success_data
            }, {
            name: "{{ __('Canceled') }}",
            color: "#dc3545",
            data: chart_one_data.canceled_data
            }, {
            name: "{{ __('Hold') }}",
            color: "#ded7e9",
            data: chart_one_data.hold_data
            }
        ],
        chart: {
            height: 350,
            type: "area",
            toolbar: {
                show: false,
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            curve: "smooth",
        },
        xaxis: {
            type: "datetime",
            categories:month_day,
        },
        tooltip: {
            x: {
                format: "dd/MM/yy HH:mm",
            },
        },
    };

    var chart = new ApexCharts(document.querySelector("#chart1"), options);
    chart.render();
//money out
var chart3 = $("#chart3");
var chart_three_data = chart3.data("chart_three_data");
var month_day = chart3.data("month_day");
// apex-chart
var options = {
    series: [
        {
            name: "{{ __('Pending') }}",
            color: "#0C56DB",
            data: chart_three_data.pending_data
            }, {
            name: "{{ __('Completed') }}",
            color: "rgba(0, 227, 150, 0.85)",
            data: chart_three_data.success_data
            }, {
            name: "{{ __('Canceled') }}",
            color: "#dc3545",
            data: chart_three_data.canceled_data
            }, {
            name: "{{ __('Hold') }}",
            color: "#ded7e9",
            data: chart_three_data.hold_data
            }
    ],
    chart: {
        type: "bar",
        height: 350,
        stacked: true,
        toolbar: {
            show: false,
        },
        zoom: {
            enabled: true,
        },
    },
    responsive: [
        {
            breakpoint: 480,
            options: {
                legend: {
                    position: "bottom",
                    offsetX: -10,
                    offsetY: 0,
                },
            },
        },
    ],
    plotOptions: {
        bar: {
            horizontal: false,
            borderRadius: 10,
        },
    },
    xaxis: {
        type: "datetime",
        categories: month_day,
    },
    legend: {
        position: "bottom",
        offsetX: 40,
    },
    fill: {
        opacity: 1,
    },
};

var chart = new ApexCharts(document.querySelector("#chart3"), options);
chart.render();

</script>
@endpush
