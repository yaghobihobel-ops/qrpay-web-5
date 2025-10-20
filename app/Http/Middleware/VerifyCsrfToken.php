<?php

namespace App\Http\Middleware;

use App\Constants\PaymentGatewayConst;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'user/username/check',
        'user/check/email',
        '/add-money/sslcommerz/success',
        '/add-money/sslcommerz/cancel',
        '/add-money/sslcommerz/fail',
        '/add-money/sslcommerz/ipn',

        '/api/add-money/sslcommerz/success',
        '/api/add-money/sslcommerz/cancel',
        '/api/add-money/sslcommerz/fail',
        '/api/add-money/sslcommerz/ipn',

        '/payment-link/gateway/payment/sslcommerz/success',
        '/payment-link/gateway/payment/sslcommerz/cancel',
        '/payment-link/gateway/payment/sslcommerz/fail',
        '/payment-link/gateway/payment/sslcommerz/ipn',

        'agent/add-money/sslcommerz/success',
        'agent/add-money/sslcommerz/fail',
        'agent/add-money/sslcommerz/cancel',

        'user/add-money/success/response/' . PaymentGatewayConst::RAZORPAY,
        'user/add-money/cancel/response/' . PaymentGatewayConst::RAZORPAY,
        'agent/add-money/success/response/' . PaymentGatewayConst::RAZORPAY,
        'agent/add-money/cancel/response/' . PaymentGatewayConst::RAZORPAY,
    ];
}
