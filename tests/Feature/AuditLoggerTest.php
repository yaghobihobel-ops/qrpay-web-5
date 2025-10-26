<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Providers\Admin\BasicSettingsProvider;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_sensitive_data_with_masking(): void
    {
        $user = User::factory()->create();

        $request = Request::create('/admin/action', 'POST', [], [], [], ['REMOTE_ADDR' => '192.168.1.10']);
        $this->app->instance('request', $request);

        /** @var AuditLogger $logger */
        $logger = $this->app->make(AuditLogger::class);

        $logger->log('sensitive.operation', [
            'user' => $user,
            'payload' => [
                'password' => 'super-secret',
                'nested' => [
                    'card_number' => '1234567890123456',
                ],
                'allowed' => 'value',
            ],
            'result' => [
                'status' => 'ok',
            ],
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sensitive.operation',
            'user_id' => $user->id,
            'status' => 'success',
            'ip_address' => '192.168.1.10',
        ]);

        $log = AuditLog::first();
        $this->assertSame('***', $log->payload['password']);
        $this->assertSame('***', $log->payload['nested']['card_number']);
        $this->assertSame('value', $log->payload['allowed']);
    }

    public function test_it_logs_application_provider_actions(): void
    {
        $request = Request::create('/admin/settings', 'GET', [], [], [], ['REMOTE_ADDR' => '10.10.10.10']);
        $this->app->instance('request', $request);

        $provider = new BasicSettingsProvider();
        $provider->set(['mode' => 'live']);
        $provider->getData();

        $setLog = AuditLog::where('action', 'basic_settings.set')->first();
        $this->assertNotNull($setLog);
        $this->assertEquals(['keys' => ['mode']], $setLog->payload);
        $this->assertEquals('10.10.10.10', $setLog->ip_address);

        $getLog = AuditLog::where('action', 'basic_settings.get')->first();
        $this->assertNotNull($getLog);
        $this->assertEquals(['keys' => ['mode']], $getLog->result);
    }
}
