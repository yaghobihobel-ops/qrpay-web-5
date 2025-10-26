<?php

return [
    'labels' => [
        'localized_guidance' => 'Localized guidance',
        'localized_guidance_intro' => 'Preview the per-locale hints we surface directly inside customer payment forms.',
        'instructions_heading' => 'Field instructions',
        'format_examples' => 'Format examples',
        'fallback_notice' => 'Enable JavaScript to view localized guidance.',
        'scenario_playbook' => 'Scenario playbook',
        'scenario_intro' => 'Interactive blueprints for QR, mobile wallet, and bank-auth flows with localized guidance.',
        'qr_flow_heading' => 'QR acceptance flow',
        'alipay_flow_heading' => 'Alipay wallet flow',
        'bank_flow_heading' => 'BluBank/Yoomonea verification',
        'steps_heading' => 'Steps',
        'compliance_heading' => 'Regulatory notes',
        'handoff_label' => 'Hand-off payload',
        'scenario_fallback' => 'Scenario details require JavaScript to render.',
    ],
    'push' => [
        'add_money' => [
            'success' => [
                'title' => 'Deposit request received',
                'body' => 'We are processing your :channel deposit of :amount. Reference: :reference.',
            ],
        ],
        'money_out' => [
            'review' => [
                'title' => 'Withdrawal request in review',
                'body' => 'Your payout request :reference for :amount is being reviewed.',
            ],
        ],
        'qr' => [
            'share' => [
                'title' => 'Share your QR code',
                'body' => 'Send this QR code to your payer. It expires on :expires.',
            ],
        ],
    ],
    'sms' => [
        'verification' => [
            'code' => 'Your QRPay verification code is :code. Do not share it with anyone.',
        ],
    ],
    'email' => [
        'add_money' => [
            'summary' => [
                'subject' => 'Deposit confirmation',
                'intro' => 'We have received your :channel deposit request for :amount.',
                'footer' => 'Local regulatory limits may apply in :country.',
            ],
        ],
        'money_out' => [
            'sender' => [
                'subject' => 'Payout confirmation',
                'intro' => 'Your payout request :reference for :amount has been received.',
                'footer' => 'We will notify you once funds arrive with the recipient in :country.',
            ],
            'receiver' => [
                'subject' => 'Incoming payout on the way',
                'intro' => 'A payout of :amount is headed to your account. Reference: :reference.',
                'footer' => 'This transfer meets :country settlement requirements.',
            ],
        ],
        'withdraw' => [
            'summary' => [
                'subject' => 'Withdrawal request submitted',
                'intro' => 'Your payout request :reference for :amount is now in review.',
                'footer' => 'We will notify you once settlement is complete for :country.',
            ],
        ],
        'alipay' => [
            'instructions' => [
                'subject' => 'Alipay checkout instructions',
                'intro' => 'Complete the payment in the Alipay app using the localized steps below.',
                'footer' => 'Ensure the payer name matches your QRPay profile to avoid delays.',
            ],
        ],
        'bank' => [
            'auth' => [
                'subject' => 'BluBank/Yoomonea authentication steps',
                'intro' => 'Follow these steps to authorize your bank account securely.',
                'footer' => 'Authentication tokens expire after 10 minutes to meet :country compliance.',
            ],
        ],
    ],
];
