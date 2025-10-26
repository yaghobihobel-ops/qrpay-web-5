<?php

namespace Tests\Feature\Security;

use App\Models\DeviceFingerprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DeviceFingerprintTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_and_trusts_device_fingerprints()
    {
        $user = User::factory()->create();

        $request = Request::create('/login', 'POST', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64)',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->headers->set('Accept-Language', 'en-US');

        $service = app(\App\Services\Security\DeviceFingerprintService::class);
        $fingerprint = $service->register($request, $user);

        $this->assertInstanceOf(DeviceFingerprint::class, $fingerprint);
        $this->assertFalse($fingerprint->is_trusted);

        $trusted = $service->trustCurrent($request, $user);
        $this->assertTrue($trusted->fresh()->is_trusted);
    }

    /** @test */
    public function it_does_not_collide_across_different_users()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $request = Request::create('/login', 'POST', [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64)',
            'REMOTE_ADDR' => '10.0.0.5',
        ]);
        $request->headers->set('Accept-Language', 'en-US');

        $service = app(\App\Services\Security\DeviceFingerprintService::class);

        $fingerprintA = $service->register($request, $userA);
        $service->trustCurrent($request, $userA);

        $fingerprintB = $service->register($request, $userB);

        $this->assertDatabaseHas('device_fingerprints', [
            'fingerprint' => $fingerprintA->fingerprint,
            'authenticatable_type' => $userA::class,
            'authenticatable_id' => $userA->getKey(),
            'is_trusted' => true,
        ]);

        $this->assertDatabaseHas('device_fingerprints', [
            'fingerprint' => $fingerprintB->fingerprint,
            'authenticatable_type' => $userB::class,
            'authenticatable_id' => $userB->getKey(),
            'is_trusted' => false,
        ]);

        $this->assertNotSame($fingerprintA->getKey(), $fingerprintB->getKey());
    }
}
