@extends('frontend.layouts.master')

@section('content')
<div class="row justify-content-center ptb-120">
    <div class="col-lg-6">
        <div class="custom-card">
            <div class="card-body">
                <div class="payment-loader-wrapper w-100">
                    <div class="payment-loader">
                        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                        </svg>
                    </div>
                    <h4 class="title py-3">{{ __('Transaction Successfully') }}.</h4>
                </div>
                <div class="col-xl-12 col-lg-12">
                    <div class="d-flex">
                        <a  href="{{ setRoute('index') }}" class="btn--base w-100 me-2">{{ __('Go To Home') }}</a>
                        <a  href="{{ route('payment-link.share', $payment_link->token) }}" class="btn--base active w-100">{{ __('Payment Again') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

