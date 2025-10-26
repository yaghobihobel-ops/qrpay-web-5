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
    <div id="user-dashboard-app" data-dashboard='@json($dashboardPayload)'></div>
    <noscript>
        <div class="mt-8 rounded-xl border border-warning bg-warning/10 p-6 text-warning">
            {{ __('For the best experience enable JavaScript to view the interactive dashboard.') }}
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
                @if($recommendation)
                    <div class="col-xxl-6 col-xl-6 col-lg-12 col-md-12 col-sm-12 mb-20">
                        <div class="dashbord-item">
                            <div class="dashboard-content">
                                <span class="sub-title">{{ __("Recommended route for you") }}</span>
                                <h3 class="title">{{ __($recommendation->label()) }}
                                    @if($recommendation->currency())
                                        <span class="text--base">{{ $recommendation->currency() }}</span>
                                    @endif
                                </h3>
                                <p class="text-muted small mb-0">{{ $recommendation->rationale() }}</p>
                            </div>
                            <div class="dashboard-icon">
                                <span class="badge badge--success">{{ __("Confidence") }}: {{ number_format($recommendation->confidence()*100, 1) }}%</span>
                            </div>
                            @if(count($recommendation->alternatives()))
                                <div class="mt-15 px-3 pb-3">
                                    <small class="text-muted d-block mb-1">{{ __("Alternatives") }}</small>
                                    <ul class="list list--base">
                                        @foreach($recommendation->alternatives() as $alternative)
                                            <li class="text-muted">{{ __($alternative['label']) }} ({{ number_format($alternative['score']*100, 1) }}%)</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
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
