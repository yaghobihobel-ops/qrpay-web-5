<?php

namespace Tests\Feature\Support;

use App\Models\User;
use App\Services\Support\HelpContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_returns_help_content(): void
    {
        /** @var HelpContentService $service */
        $service = $this->app->make(HelpContentService::class);

        $content = $service->getContent('payments');

        $this->assertSame('payments', $content['section']);
        $this->assertNotEmpty($content['content']);
        $this->assertSame('Payments', $content['title']);
    }

    public function test_authenticated_user_can_fetch_help_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/help/payments');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'section',
                'title',
                'summary',
                'video',
                'content',
            ],
        ]);
    }

    public function test_help_icon_view_contains_trigger_attributes(): void
    {
        $view = view('user.components.help-icon', ['section' => 'payments'])->render();

        $this->assertStringContainsString('data-help-section="payments"', $view);
        $this->assertStringContainsString('help-icon-button', $view);
    }
}
