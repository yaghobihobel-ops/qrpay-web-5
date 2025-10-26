<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiVersioningTest extends TestCase
{
    public function test_v1_version_info_route_is_accessible(): void
    {
        $response = $this->getJson('/api/v1/version-info');

        $response->assertOk()->assertJson([
            'version' => 'v1',
        ]);
    }

    public function test_v2_version_info_route_is_accessible(): void
    {
        $response = $this->getJson('/api/v2/version-info');

        $response->assertOk()->assertJson([
            'version' => 'v2',
        ]);
    }

    public function test_accept_version_header_routes_without_explicit_prefix(): void
    {
        $response = $this->getJson('/api/version-info', ['Accept-Version' => 'v2']);

        $response->assertOk()->assertJson([
            'version' => 'v2',
        ]);
    }

    public function test_invalid_accept_version_falls_back_to_v1(): void
    {
        $response = $this->getJson('/api/version-info', ['Accept-Version' => 'v9']);

        $response->assertOk()->assertJson([
            'version' => 'v1',
        ]);
    }

    public function test_fallback_returns_json_payload(): void
    {
        $response = $this->getJson('/api/unknown-endpoint');

        $response->assertNotFound()->assertJson([
            'message' => 'Resource not found.',
            'version' => 'v1',
        ]);
    }
}
