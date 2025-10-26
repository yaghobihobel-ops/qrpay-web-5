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
        <div class="dashboard-header-wrapper">
            <h3 class="title">{{__(@$page_title)}}</h3>
        </div>
    </div>
    <div class="row mb-30-none">
        <div class="col-xl-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{ __(@$page_title) }}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <form class="card-form" action="{{ setRoute('user.make.payment.confirmed') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-xl-12 col-lg-12 form-group text-center">
                                    <div class="exchange-area">
                                        <code class="d-block text-center"><span class="fees-show">--</span> <span class="limit-show">--</span> <span class="rate-show">--</span></code>
                                    </div>
                                </div>
                                <div class="col-xxl-6 col-xl-12 col-lg-6 form-group paste-wrapper">
                                    <label>{{ __("email Address") }} ({{ __("Merchant") }})<span class="text--base">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text copytext"><span>{{ __("Email") }}</span></span>
                                        </div>
                                        <input type="email" name="email" class="form--control checkUser" id="username" placeholder="{{ __("enter Email Address") }}" value="{{ old('email') }}" />
                                    </div>
                                    <button type="button" class="paste-badge scan"  data-toggle="tooltip" title="Scan QR"><i class="fas fa-camera"></i></button>
                                    <label class="exist text-start"></label>

                                </div>

                                <div class="col-xxl-6 col-xl-12 col-lg-6 form-group">
                                    <label>{{ __("Amount") }}<span>*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form--control number-input" required placeholder="{{ __("enter Amount") }}" name="amount" value="{{ old("amount") }}">
                                        <select class="form--control nice-select currency" name="currency">
                                            <option value="{{ get_default_currency_code() }}">{{ get_default_currency_code() }}</option>
                                        </select>
                                    </div>
                                    <code class="d-block mt-10 text-end text--warning balance-show">{{ __("Available Balance") }} {{ authWalletBalance() }} {{ get_default_currency_code() }}</code>
                                </div>

                                <div class="col-xl-12 col-lg-12">
                                    <button type="submit" class="btn--base w-100 btn-loading transfer">{{ __("Confirm Payment") }} <i class="fas fa-paper-plane ms-1"></i></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-30">
            <div class="dash-payment-item-wrapper">
                <div class="dash-payment-item active">
                    <div class="dash-payment-title-area">
                        <span class="dash-payment-badge">!</span>
                        <h5 class="title">{{__("Preview")}}</h5>
                    </div>
                    <div class="dash-payment-body">
                        <div class="preview-list-wrapper">

                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-coins"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Entered Amount") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="fw-bold request-amount">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-battery-half"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Total Fees") }}</span>
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
                                            <i class="las la-receipt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{ __("Recipient Received") }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="recipient-get">--</span>
                                </div>
                            </div>
                            <div class="preview-list-item">
                                <div class="preview-list-left">
                                    <div class="preview-list-user-wrapper">
                                        <div class="preview-list-user-icon">
                                            <i class="las la-money-check-alt"></i>
                                        </div>
                                        <div class="preview-list-user-content">
                                            <span>{{__("Total Payable")}}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="preview-list-right">
                                    <span class="last payable-total text-warning">--</span>
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
<div class="modal fade" id="scanModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
            <div class="modal-body text-center">
                <video id="preview" class="p-1 border" style="width:300px;"></video>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">@lang('close')</button>
            </div>
      </div>
    </div>
</div>
@endsection

@push('script')
<script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
<script>
//  'use strict'
    (function ($) {
        $('.scan').click(function(){
            var scanner = new Instascan.Scanner({ video: document.getElementById('preview'), scanPeriod: 5, mirror: false });
            scanner.addListener('scan',function(content){
                var route = '{{url('user/merchant/qr/scan/')}}'+'/'+content
                $.get(route, function( data ) {
                    if(data.error){
                        // alert(data.error)
                        throwMessage('error',[data.error]);
                    } else {
                        $("#username").val(data);
                        $("#username").focus()
                    }
                    $('#scanModal').modal('hide')
                });
            });

            Instascan.Camera.getCameras().then(function (cameras){
                if(cameras.length>0){
                    $('#scanModal').modal('show')
                        scanner.start(cameras[0]);
                } else{
                    throwMessage('error',["No camera found "]);
                }
            }).catch(function(e){
                throwMessage('error',["No camera found "]);
            });
        });
        $('.checkUser').on('keyup',function(e){
            var url = '{{ route('user.make.payment.check.exist') }}';
            var value = $(this).val();
            var token = '{{ csrf_token() }}';
            if ($(this).attr('name') == 'email') {
                var data = {email:value,_token:token}

            }
            $.post(url,data,function(response) {
                if(response.own){
                    if($('.exist').hasClass('text--success')){
                        $('.exist').removeClass('text--success');
                    }
                    $('.exist').addClass('text--danger').text(response.own);
                    $('.transfer').attr('disabled',true)
                    return false
                }
                if(response['data'] != null){
                    if($('.exist').hasClass('text--danger')){
                        $('.exist').removeClass('text--danger');
                    }
                    $('.exist').text(`Valid merchant for transaction.`).addClass('text--success');
                    $('.transfer').attr('disabled',false)
                } else {
                    if($('.exist').hasClass('text--success')){
                        $('.exist').removeClass('text--success');
                    }
                    $('.exist').text('Merchant doesn\'t  exists.').addClass('text--danger');
                    $('.transfer').attr('disabled',true)
                    return false
                }

            });
        });
    })(jQuery);
</script>
<script>
    var defualCurrency = "{{ get_default_currency_code() }}";
    var defualCurrencyRate = "{{ get_default_currency_rate() }}";
    var pricingContext = @json($pricingContext);
    var pricingEndpoint = "{{ setRoute('user.pricing.quote') }}";
    var csrfToken = "{{ csrf_token() }}";

    $(document).ready(function(){
        getLimit();
        updatePreview();

        $("input[name=amount]").on('input', function(){
            updatePreview();
        });

        $("input[name=amount]").on('focusout', function(){
            enterLimit();
        });
    });

    function getLimit() {
        var min_limit = parseFloat("{{ getAmount($makePaymentCharge->min_limit) }}");
        var max_limit = parseFloat("{{ getAmount($makePaymentCharge->max_limit) }}");

        if($.isNumeric(min_limit) || $.isNumeric(max_limit)) {
            var min_limit_calc = parseFloat(min_limit / defualCurrencyRate).toFixed(2);
            var max_limit_calc = parseFloat(max_limit / defualCurrencyRate).toFixed(2);
            $('.limit-show').html("{{ __('limit') }} " + min_limit_calc + " " + defualCurrency + " - " + max_limit_calc + " " + defualCurrency);
            return {
                minLimit: min_limit_calc,
                maxLimit: max_limit_calc,
            };
        } else {
            $('.limit-show').html("--");
            return {
                minLimit: 0,
                maxLimit: 0,
            };
        }
    }

    function updatePreview() {
        var senderAmount = parseFloat($("input[name=amount]").val());
        if(!$.isNumeric(senderAmount) || senderAmount <= 0) {
            resetQuote();
            return;
        }

        $('.request-amount').text(senderAmount.toFixed(2) + " " + defualCurrency);
        fetchQuote(senderAmount);
    }

    function fetchQuote(amount) {
        $('.fees-show').text('{{ __('Calculating...') }}');

        $.ajax({
            method: 'POST',
            url: pricingEndpoint,
            data: {
                _token: csrfToken,
                currency: defualCurrency,
                amount: amount,
                transaction_type: pricingContext.transaction_type,
                provider: pricingContext.provider,
                user_level: pricingContext.user_level
            },
            success: function(response) {
                if(response.quote) {
                    applyQuote(amount, response.quote);
                } else {
                    resetQuote();
                }
            },
            error: function(xhr) {
                resetQuote();
                if(xhr.responseJSON && xhr.responseJSON.error) {
                    throwMessage('error', [xhr.responseJSON.error]);
                }
            }
        });
    }

    function applyQuote(amount, quote) {
        var totalCharge = parseFloat(quote.total_fee);
        var fixedCharge = parseFloat(quote.fixed_fee);
        var appliedPercent = parseFloat(quote.applied_percent);

        var exchangeRate = parseFloat(quote.exchange_rate);
        $('.fees-show').html("{{ __('Payment Fee') }}: " + fixedCharge.toFixed(2) + " " + defualCurrency + " + " + appliedPercent.toFixed(2) + "%");
        $('.rate-show').text('{{ __('Rate') }}: ' + exchangeRate.toFixed(6));
        $('.fees').text(totalCharge.toFixed(2) + " " + defualCurrency);

        var recipient = amount * defualCurrencyRate;
        $('.recipient-get').text(recipient.toFixed(2) + " " + defualCurrency);

        var payInTotal = amount + totalCharge;
        $('.payable-total').text(payInTotal.toFixed(2) + " " + defualCurrency);
    }

    function resetQuote() {
        $('.fees-show').text('--');
        $('.rate-show').text('--');
        $('.fees').text('--');
        $('.recipient-get').text('0.00 ' + defualCurrency);
        $('.payable-total').text('0.00 ' + defualCurrency);
    }

    function enterLimit(){
        var min_limit = parseFloat("{{ getAmount($makePaymentCharge->min_limit) }}");
        var max_limit = parseFloat("{{ getAmount($makePaymentCharge->max_limit) }}");
        var sender_amount = parseFloat($("input[name=amount]").val());

        if(sender_amount < min_limit){
            throwMessage('error',['{{ __("Please follow the mimimum limit") }}']);
            $('.transfer').attr('disabled',true)
        }else if(sender_amount > max_limit){
            throwMessage('error',['{{ __("Please follow the maximum limit") }}']);
            $('.transfer').attr('disabled',true)
        }else{
            $('.transfer').attr('disabled',false)
        }
    }
</script>

@endpush
