<?php

namespace Tests\Feature;

use App\Constants\PaymentGatewayConst;
use App\Models\Admin\Admin;
use App\Models\Admin\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\VirtualCardApi;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserExperienceE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::factory()->create(['id' => 1]);

        Currency::factory()->create([
            'admin_id' => $admin->id,
            'name' => 'US Dollar',
            'code' => 'USD',
            'symbol' => '$',
            'rate' => 1,
            'default' => true,
            'sender' => true,
            'receiver' => true,
        ]);

        VirtualCardApi::create([
            'admin_id' => $admin->id,
            'config' => ['name' => 'flutterwave'],
        ]);
    }

    public function test_dashboard_payload_structure(): void
    {
        $user = User::factory()->create();
        $currency = Currency::default();
        $wallet = UserWallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'balance' => 5000,
            'status' => true,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'user_wallet_id' => $wallet->id,
            'type' => PaymentGatewayConst::TYPEADDMONEY,
            'trx_id' => 'TRX-ADD',
            'request_amount' => 1200,
            'payable' => 1200,
            'available_balance' => 3800,
            'remark' => 'Add money',
            'status' => PaymentGatewayConst::STATUSSUCCESS,
            'attribute' => PaymentGatewayConst::SEND,
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'user_wallet_id' => $wallet->id,
            'type' => PaymentGatewayConst::TYPEMONEYOUT,
            'trx_id' => 'TRX-WD',
            'request_amount' => 300,
            'payable' => 300,
            'available_balance' => 3500,
            'remark' => 'Withdraw',
            'status' => PaymentGatewayConst::STATUSSUCCESS,
            'attribute' => PaymentGatewayConst::SEND,
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->actingAs($user, 'web')->get(route('user.dashboard'));

        $response->assertOk();
        $response->assertViewHas('dashboardPayload');

        $payload = $response->viewData('dashboardPayload');
        $this->assertArrayHasKey('summary', $payload);
        $this->assertArrayHasKey('analytics', $payload);
        $this->assertArrayHasKey('chart', $payload);
        $this->assertSame('USD', $payload['currency']);
        $this->assertNotEmpty($payload['summary']);
        $this->assertNotEmpty($payload['chart']['categories']);
        $this->assertArrayHasKey('languages', $payload);
        $this->assertTrue(collect($payload['languages'])->contains(fn ($lang) => $lang['code'] === 'zh'));
    }

    public function test_user_can_update_preferences(): void
    {
        $user = User::factory()->create([
            'preferred_theme' => 'light',
            'preferred_language' => 'en',
            'notification_preferences' => [
                'email' => true,
                'sms' => false,
                'push' => true,
            ],
        ]);

        $response = $this->actingAs($user, 'web')->postJson(route('user.preferences.update'), [
            'theme' => 'dark',
            'language' => 'zh',
            'notifications' => [
                'email' => false,
                'sms' => true,
            ],
        ]);

        $response->assertOk()->assertJsonFragment([
            'status' => 'ok',
        ]);

        $user->refresh();
        $this->assertSame('dark', $user->preferred_theme);
        $this->assertSame('zh', $user->preferred_language);
        $this->assertEquals([
            'email' => false,
            'sms' => true,
            'push' => true,
        ], $user->notification_preferences);
        $this->assertSame('zh', session('lang'));
    }
}
