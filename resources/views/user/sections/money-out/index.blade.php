@extends('user.layouts.master')

@push('css')

@endpush

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
    <div class="dashboard-area mt-10">
        <div class="dashboard-header-wrapper d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <h3 class="title mb-0">{{__($page_title)}}</h3>
                <x-help.launcher section="withdrawals" :label="__('Withdrawal help')" />
            </div>
        </div>
    </div>
    <div class="row mb-30-none">
        <div class="col-lg-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __($page_title) }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <form class="card-form" action="{{ setRoute('user.money.out.insert') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 form-group text-center">
                                    <div class="exchange-area">
                                        <code class="d-block text-center"><span>{{ __("Exchange Rate") }}</span> <span class="rate-show">--</span></code>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">
                                    <label>{{ __("Payment Gateway") }}<span>*</span></label>
                                    <select class="form--control nice-select gateway-select" name="gateway">
                                        @forelse ($payment_gateways ?? [] as $item)
                                            <option
                                                value="{{ $item->alias  }}"
                                                data-currency="{{ $item->currency_code }}"
                                                data-min_amount="{{ $item->min_limit }}"
                                                data-max_amount="{{ $item->max_limit }}"
                                                data-percent_charge="{{ $item->percent_charge }}"
                                                data-fixed_charge="{{ $item->fixed_charge }}"
                                                data-rate="{{ $item->rate }}"
                                                data-crypto="{{ $item->gateway->crypto}}"
                                                >
                                                {{ $item->name }}
                                            </option>
                                        @empty
                                            <option value="null" disabled  selected>{{ __('No Gateway Available') }}</option>
                                        @endforelse
                                    </select>
                                </div>
                                <div class="col-xl-6 col-lg-6 form-group">

                                    <label>{{ __("Amount") }}<span>*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form--control" required placeholder="{{ __('enter Amount') }}" name="amount" value="{{ old("amount") }}">
                                        <select class="form--control nice-select">
                                            <option value="{{ get_default_currency_code() }}">{{ get_default_currency_code() }}</option>
                                        </select>
                                    </div>
                                    <code class="d-block mt-10 text-end text--dark fw-bold balance-show">{{ __("Available Balance") }} {{ authWalletBalance() }} {{ get_default_currency_code() }}</code>
                                </div>
                                <div class="col-xl-12 col-lg-12 form-group">
                                    <div class="note-area">
                                        <code class="d-block limit-show">--</code>
                                        <code class="d-block fees-show">--</code>
                                    </div>
                                </div>
                                <div class="col-xl-12 col-lg-12">
                                    <button type="submit" class="btn--base w-100 btn-loading">{{ __("Withdraw Money") }} <i class="fas fa-arrow-alt-circle-right ms-1"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __($page_title) }} {{__("Preview")}}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="preview-list-wrapper">
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-receipt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Entered Amount") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="request-amount">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="lab la-get-pocket"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Conversion Amount") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="conversionAmount">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-battery-half"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Total Fees & Charges") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="fees">--</span>
                                </div>
                            </div>

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span class="">{{ __("Will Get") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--success will-get">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span class="last">{{ __("Payable Amount") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="text--warning last total-pay">--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

@push('script')
    <script>
        const baseCurrency = "{{ get_default_currency_code() }}";
        const quoteUrl = "{{ setRoute('user.money.out.quote') }}";
        let quoteRequest = null;
        let quoteTimer = null;

        function selectedGatewayMeta() {
            const selected = $("select[name=gateway] :selected");
            return {
                rate: parseFloat(selected.data('rate')) || 0,
                min: parseFloat(selected.data('min_amount')) || 0,
                max: parseFloat(selected.data('max_amount')) || 0,
                currency: selected.data('currency') || baseCurrency,
                isValid: selected.length && selected.val() !== 'null',
            };
        }

        function setFallbackPreview() {
            const meta = selectedGatewayMeta();
            if (!meta.isValid) {
                resetPreview();
                return;
            }
            if (meta.rate > 0 && (meta.min > 0 || meta.max > 0)) {
                const minBase = meta.min > 0 ? (meta.min / meta.rate).toFixed(2) : null;
                const maxBase = meta.max > 0 ? (meta.max / meta.rate).toFixed(2) : null;
                if (minBase && maxBase) {
                    $('.limit-show').html(`{{ __('limit') }} ${minBase} ${baseCurrency} - ${maxBase} ${baseCurrency}`);
                }
            }
        }

        function resetPreview() {
            $('.rate-show').html('--');
            $('.request-amount').text(`0 ${baseCurrency}`);
            $('.conversionAmount').text('--');
            $('.fees').text('--');
            $('.will-get').text('--');
            $('.total-pay').text(`0 ${baseCurrency}`);
            $('.fees-show').html('--');
            $('.limit-show').html('--');
        }

        function updatePreview(quote) {
            if (!quote || !quote.data) {
                resetPreview();
                return;
            }

            const data = quote.data;
            const formatted = quote.formatted || {};

            $('.rate-show').html(`1 ${baseCurrency} = ${formatted.exchange_rate ?? data.exchange_rate} ${data.provider_currency}`);
            $('.request-amount').text(`${Number(data.requested_amount).toFixed(2)} ${baseCurrency}`);
            $('.conversionAmount').text(`${formatted.provider_amount ?? data.provider_amount} ${data.provider_currency}`);
            $('.fees').text(`${formatted.fee_amount ?? data.fee.amount} ${data.provider_currency}`);
            $('.will-get').text(`${formatted.net_provider ?? data.net_amount_provider} ${data.provider_currency}`);
            $('.total-pay').text(`${Number(data.requested_amount).toFixed(2)} ${baseCurrency}`);

            if (formatted.limits && formatted.limits.min_base && formatted.limits.max_base) {
                $('.limit-show').html(`{{ __('limit') }} ${formatted.limits.min_base} ${baseCurrency} - ${formatted.limits.max_base} ${baseCurrency}`);
            } else {
                setFallbackPreview();
            }

            const feeParts = [];
            if (data.pricing_rule_id) {
                feeParts.push(`{{ __('Rule') }} #${data.pricing_rule_id}`);
            }
            if (data.pricing_tier_id) {
                feeParts.push(`{{ __('Tier') }} #${data.pricing_tier_id}`);
            }
            if (data.fee.type) {
                feeParts.push(data.fee.type);
            }

            const feeLabel = data.fee.source === 'gateway_default'
                ? `{{ __('Gateway fee') }}`
                : `{{ __('Fee engine') }}`;

            const feeDescription = feeParts.length ? ` (${feeParts.join(' · ')})` : '';
            $('.fees-show').html(`${feeLabel}: ${formatted.fee_amount ?? data.fee.amount} ${data.provider_currency}${feeDescription}`);
        }

        function showQuoteError(message) {
            $('.fees-show').html(`<span class="text--danger">${message}</span>`);
        }

        function requestQuote() {
            const meta = selectedGatewayMeta();
            const amount = parseFloat($("input[name=amount]").val());

            if (!meta.isValid || !amount || amount <= 0) {
                resetPreview();
                setFallbackPreview();
                return;
            }

            if (quoteRequest) {
                quoteRequest.abort();
            }

            quoteRequest = $.ajax({
                method: 'POST',
                url: quoteUrl,
                data: {
                    gateway: $("select[name=gateway]").val(),
                    amount: amount,
                    _token: '{{ csrf_token() }}'
                },
                beforeSend() {
                    $('.fees-show').html('{{ __('Calculating fees...') }}');
                },
                success(response) {
                    updatePreview(response);
                },
                error(xhr) {
                    resetPreview();
                    setFallbackPreview();
                    const message = xhr?.responseJSON?.message ?? '{{ __('Unable to fetch quote at this time.') }}';
                    showQuoteError(message);
                },
                complete() {
                    quoteRequest = null;
                }
            });
        }

        function scheduleQuote() {
            if (quoteTimer) {
                clearTimeout(quoteTimer);
            }
            quoteTimer = setTimeout(requestQuote, 300);
        }

        $('select[name=gateway]').on('change', function(){
            resetPreview();
            setFallbackPreview();
            scheduleQuote();
        });

        $(document).ready(function(){
            resetPreview();
            setFallbackPreview();
            scheduleQuote();
        });

        $("input[name=amount]").on('input', function(){
            scheduleQuote();
        });
    </script>
@endpush

