<?php

namespace Tests\Feature\Compliance;

use App\Models\User;
use App\Services\Compliance\RuleEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmlRuleEngineTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_scores_and_flags_high_risk_rules_for_china()
    {
        $user = User::factory()->create([
            'address' => (object) ['country' => 'CN'],
        ]);

        $engine = app(RuleEngine::class);
        $decision = $engine->evaluate($user, [
            'country' => 'CN',
            'expected_monthly_volume' => 100000,
            'is_cross_border' => true,
            'industry' => 'CRYPTO',
        ]);

        $this->assertSame('escalate', $decision->status());
        $this->assertGreaterThanOrEqual(70, $decision->riskScore());
        $this->assertTrue($decision->requiresManualReview());
        $this->assertTrue($decision->requiresEnhancedDueDiligence());
        $this->assertNotEmpty($decision->triggeredRules());
    }

    /** @test */
    public function it_returns_pass_for_low_risk_profiles()
    {
        $user = User::factory()->create([
            'address' => (object) ['country' => 'TR'],
        ]);

        $decision = app(RuleEngine::class)->evaluate($user, [
            'country' => 'TR',
            'industry' => 'SOFTWARE',
            'expected_monthly_volume' => 1000,
        ]);

        $this->assertSame('pass', $decision->status());
        $this->assertSame(0, $decision->riskScore());
        $this->assertFalse($decision->requiresManualReview());
    }
}
