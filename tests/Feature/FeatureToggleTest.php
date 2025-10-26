<?php

namespace Tests\Feature;

use App\Services\Deployment\FeatureToggle;
use Illuminate\Http\Request;
use Tests\TestCase;

class FeatureToggleTest extends TestCase
{
    public function test_feature_is_enabled_when_flag_is_true(): void
    {
        config(['features.features.demo' => ['enabled' => true]]);
        $toggle = app(FeatureToggle::class);

        $this->assertTrue($toggle->isEnabled('demo'));
        $this->assertSame(100, $toggle->percentage('demo'));
    }

    public function test_canary_release_uses_percentage_bucket(): void
    {
        config(['features.features.rollout' => [
            'enabled' => false,
            'canary' => [
                'enabled' => true,
                'percentage' => 50,
                'identifier' => 'email',
            ],
        ]]);

        $toggle = app(FeatureToggle::class);
        $request = Request::create('/', 'GET');
        $user = (object) ['email' => 'user@example.com'];

        $first = $toggle->isEnabled('rollout', $user, $request);
        $second = $toggle->isEnabled('rollout', $user, $request);

        $this->assertSame($first, $second, 'Canary evaluation should be deterministic for the same identifier.');
    }
}
