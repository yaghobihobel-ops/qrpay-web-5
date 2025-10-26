<?php

return [
    'labels' => [
        'localized_guidance' => 'راهنمای بومی‌سازی',
        'localized_guidance_intro' => 'نمونه‌ای از نکاتی که به صورت چندزبانه داخل فرم‌های پرداخت نمایش داده می‌شود.',
        'instructions_heading' => 'توضیحات تکمیل فیلدها',
        'format_examples' => 'نمونه قالب‌ها',
        'fallback_notice' => 'برای مشاهده راهنما جاوااسکریپت را فعال کنید.',
        'scenario_playbook' => 'نقشه سناریو',
        'scenario_intro' => 'نقشه‌های تعاملی برای جریان‌های QR، کیف پول موبایلی و احراز هویت بانکی.',
        'qr_flow_heading' => 'جریان پذیرش QR',
        'alipay_flow_heading' => 'فرآیند کیف پول Alipay',
        'bank_flow_heading' => 'تأیید BluBank/Yoomonea',
        'steps_heading' => 'مراحل',
        'compliance_heading' => 'نکات مقرراتی',
        'handoff_label' => 'داده تحویل به سیستم',
        'scenario_fallback' => 'برای مشاهده جزئیات سناریو جاوااسکریپت را فعال کنید.',
    ],
    'push' => [
        'add_money' => [
            'success' => [
                'title' => 'درخواست واریز ثبت شد',
                'body' => 'واریز :amount از طریق :channel در حال پردازش است. مرجع: :reference.',
            ],
        ],
        'money_out' => [
            'review' => [
                'title' => 'درخواست برداشت در حال بررسی است',
                'body' => 'درخواست برداشت :reference به مبلغ :amount در صف بررسی قرار گرفت.',
            ],
        ],
        'qr' => [
            'share' => [
                'title' => 'اشتراک‌گذاری کد QR',
                'body' => 'این کد را برای پرداخت‌کننده ارسال کنید. زمان انقضا: :expires.',
            ],
        ],
    ],
    'sms' => [
        'verification' => [
            'code' => 'کد تأیید QRPay شما :code است. آن را در اختیار دیگران قرار ندهید.',
        ],
    ],
    'email' => [
        'add_money' => [
            'summary' => [
                'subject' => 'تأیید درخواست واریز',
                'intro' => 'درخواست واریز شما از طریق :channel به مبلغ :amount دریافت شد.',
                'footer' => 'محدودیت‌های مقرراتی محلی در :country ممکن است اعمال شود.',
            ],
        ],
        'money_out' => [
            'sender' => [
                'subject' => 'تأیید برداشت',
                'intro' => 'درخواست برداشت :reference به مبلغ :amount دریافت شد.',
                'footer' => 'به محض واریز وجه به گیرنده در :country شما را مطلع می‌کنیم.',
            ],
            'receiver' => [
                'subject' => 'واریز در راه است',
                'intro' => 'مبلغ :amount در حال واریز به حساب شماست. مرجع: :reference.',
                'footer' => 'این انتقال مطابق الزامات تسویه در :country انجام می‌شود.',
            ],
        ],
        'withdraw' => [
            'summary' => [
                'subject' => 'درخواست برداشت ثبت شد',
                'intro' => 'درخواست برداشت :reference به مبلغ :amount در حال بررسی است.',
                'footer' => 'پس از تکمیل تسویه در :country نتیجه اطلاع‌رسانی می‌شود.',
            ],
        ],
        'alipay' => [
            'instructions' => [
                'subject' => 'راهنمای پرداخت Alipay',
                'intro' => 'برای تکمیل تراکنش، مراحل بومی‌سازی شده زیر را در اپلیکیشن Alipay دنبال کنید.',
                'footer' => 'نام پرداخت‌کننده باید با مشخصات QRPay شما یکسان باشد تا تأخیری رخ ندهد.',
            ],
        ],
        'bank' => [
            'auth' => [
                'subject' => 'گام‌های احراز هویت BluBank/Yoomonea',
                'intro' => 'برای تأیید حساب بانکی خود مراحل زیر را با ایمنی کامل انجام دهید.',
                'footer' => 'توکن احراز هویت برای رعایت الزامات :country پس از ۱۰ دقیقه منقضی می‌شود.',
            ],
        ],
    ],
];
