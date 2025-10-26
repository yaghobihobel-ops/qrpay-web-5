<?php

return [
    'labels' => [
        'localized_guidance' => '本地化填写指南',
        'localized_guidance_intro' => '展示我们在支付表单中提供的多语言提示。',
        'instructions_heading' => '填写说明',
        'format_examples' => '格式示例',
        'fallback_notice' => '启用 JavaScript 以查看本地化指南。',
        'scenario_playbook' => '场景流程图',
        'scenario_intro' => '交互式蓝图，涵盖二维码、移动钱包与银行认证流程。',
        'qr_flow_heading' => '二维码收款流程',
        'alipay_flow_heading' => '支付宝钱包流程',
        'bank_flow_heading' => 'BluBank/Yoomonea 认证流程',
        'steps_heading' => '操作步骤',
        'compliance_heading' => '监管提示',
        'handoff_label' => '系统交互负载',
        'scenario_fallback' => '请启用 JavaScript 以查看场景详情。',
    ],
    'push' => [
        'add_money' => [
            'success' => [
                'title' => '充值请求已收到',
                'body' => '我们正在处理您通过 :channel 提交的 :amount 充值，参考号：:reference。',
            ],
        ],
        'money_out' => [
            'review' => [
                'title' => '提现申请审核中',
                'body' => '您的提现申请 :reference 金额 :amount 正在审核。',
            ],
        ],
        'qr' => [
            'share' => [
                'title' => '分享二维码收款',
                'body' => '将此二维码发送给付款人，失效时间：:expires。',
            ],
        ],
    ],
    'sms' => [
        'verification' => [
            'code' => '您的 QRPay 验证码为 :code，请勿泄露。',
        ],
    ],
    'email' => [
        'add_money' => [
            'summary' => [
                'subject' => '充值确认',
                'intro' => '我们已收到您通过 :channel 提交的 :amount 充值请求。',
                'footer' => '请注意 :country 的本地监管限额可能适用。',
            ],
        ],
        'money_out' => [
            'sender' => [
                'subject' => '提现确认',
                'intro' => '您的提现请求 :reference 金额 :amount 已收到。',
                'footer' => '一旦款项在 :country 的收款方到账，我们会通知您。',
            ],
            'receiver' => [
                'subject' => '收款即将到账',
                'intro' => '金额 :amount 的付款正汇入您的账户。参考号：:reference。',
                'footer' => '该笔转账符合 :country 的结算要求。',
            ],
        ],
        'withdraw' => [
            'summary' => [
                'subject' => '提现请求已提交',
                'intro' => '您的提现请求 :reference 金额 :amount 正在审核。',
                'footer' => '一旦完成 :country 的结算，我们会第一时间通知您。',
            ],
        ],
        'alipay' => [
            'instructions' => [
                'subject' => '支付宝支付指引',
                'intro' => '请按照以下本地化步骤在支付宝应用内完成付款。',
                'footer' => '请确保付款人姓名与您的 QRPay 账户一致，以免延误。',
            ],
        ],
        'bank' => [
            'auth' => [
                'subject' => 'BluBank/Yoomonea 认证步骤',
                'intro' => '请按步骤安全授权您的银行账户。',
                'footer' => '为满足 :country 的合规要求，认证令牌 10 分钟后失效。',
            ],
        ],
    ],
];
