<?php

namespace Tests\Unit\Services;

use App\Services\Notifications\LocalizedMessagingService;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class LocalizedMessagingServiceTest extends TestCase
{
    public function test_email_template_returns_localized_copy()
    {
        $service = new LocalizedMessagingService();

        $placeholders = [
            'channel' => 'Alipay',
            'amount' => '100 CNY',
            'country' => 'CN',
            'reference' => 'TX-100',
        ];

        $email = $service->emailTemplate('add_money.summary', $placeholders, [
            'country' => 'CN',
        ]);

        $expected = Lang::get('messaging.email.add_money.summary.subject', $placeholders, 'zh');

        $this->assertSame('zh', $email['locale']);
        $this->assertSame('CN', $email['country']);
        $this->assertSame($expected, $email['subject']);
    }

    public function test_transform_applies_country_rules_to_push_messages()
    {
        $service = new LocalizedMessagingService();

        $payload = [
            'template' => 'add_money.success',
            'placeholders' => [
                'channel' => 'Bank Transfer',
                'amount' => '500 USD',
                'reference' => 'ABC123',
            ],
        ];

        $message = $service->transform('push', $payload, [
            'country' => 'CN',
        ]);

        $this->assertSame('zh', $message['locale']);
        $this->assertStringContainsString('ABC123', $message['desc']);
        $this->assertStringContainsString('遵循', $message['desc']);
    }

    public function test_sms_template_applies_signature_and_suffix()
    {
        $service = new LocalizedMessagingService();

        $sms = $service->smsTemplate('verification.code', [
            'code' => '998877',
        ], [
            'country' => 'IR',
        ]);

        $this->assertSame('fa', $sms['locale']);
        $this->assertSame('IR', $sms['country']);
        $this->assertStringContainsString('QRPay', $sms['message']);
        $this->assertStringContainsString('998877', $sms['message']);
    }

    public function test_resolve_user_context_extracts_country_from_address()
    {
        $service = new LocalizedMessagingService();

        $user = new class {
            public $address;

            public function __construct()
            {
                $this->address = [
                    'country_code' => 'RU',
                ];
            }
        };

        $context = $service->resolveUserContext($user);

        $this->assertSame('RU', $context['country']);
        $this->assertSame('ru', $context['locale']);
    }
}

