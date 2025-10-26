<?php

namespace Tests\Contract;

use Tests\TestCase;

class ProviderHealthConfigurationTest extends TestCase
{
    public function test_provider_health_checks_are_configured_with_thresholds(): void
    {
        $config = config('monitoring.providers');
        $this->assertNotEmpty($config, 'Monitoring providers configuration is missing.');

        foreach ($config as $name => $settings) {
            $this->assertArrayHasKey('endpoint', $settings, "$name endpoint not configured");
            $this->assertArrayHasKey('timeout', $settings, "$name timeout not configured");
            $this->assertArrayHasKey('latency_warning', $settings, "$name latency warning not configured");
            $this->assertArrayHasKey('max_error_rate', $settings, "$name max error rate not configured");
            $this->assertArrayHasKey('max_fee', $settings, "$name max fee not configured");
        }
    }
}
