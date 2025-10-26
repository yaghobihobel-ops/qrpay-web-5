<?php

namespace Tests\Feature\Auth;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Token;
use Tests\TestCase;

class TokenServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $clientRepository = $this->app->make(ClientRepository::class);
        $clientRepository->createPersonalAccessClient(null, 'Test Personal Access Client', config('app.url', 'http://localhost'));

        $this->service = $this->app->make(TokenService::class);
    }

    public function test_issue_and_parse_jwt_token_with_roles_and_permissions(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('finance-admin'));
        $user->assignPermission(Permission::findOrCreate('transactions.approve'));

        $result = $this->service->issueJwtToken($user, ['scopes' => ['payments.manage']]);

        $payload = $this->service->parseJwtToken($result['access_token']);

        $this->assertSame($user->getAuthIdentifier(), $payload['sub']);
        $this->assertContains('finance-admin', $payload['roles']);
        $this->assertContains('transactions.approve', $payload['permissions']);
        $this->assertContains('payments.manage', $payload['scopes']);
        $this->assertFalse($this->service->isJwtTokenRevoked($result['jti']));
    }

    public function test_refresh_jwt_token_revokes_original(): void
    {
        $user = User::factory()->create();

        $original = $this->service->issueJwtToken($user, ['scopes' => ['payments.manage']]);
        $refreshed = $this->service->refreshJwtToken($original['access_token']);

        $this->assertNotSame($original['jti'], $refreshed['jti']);
        $this->assertTrue($this->service->isJwtTokenRevoked($original['jti']));

        $payload = $this->service->parseJwtToken($refreshed['access_token']);
        $this->assertContains('payments.manage', $payload['scopes']);
    }

    public function test_issue_and_refresh_personal_access_token(): void
    {
        $user = User::factory()->create();

        $issued = $this->service->issuePersonalAccessToken($user, 'QA Token', ['payments.manage']);

        $this->assertNotEmpty($issued['access_token']);
        $this->assertSame(['payments.manage'], $issued['abilities']);

        $token = Token::query()->find($issued['token_id']);
        $this->assertFalse((bool) $token->revoked);

        $refreshed = $this->service->refreshPersonalAccessToken($user, $token, 'QA Token', ['payments.manage']);

        $token->refresh();
        $this->assertTrue((bool) $token->revoked);
        $this->assertNotSame($issued['access_token'], $refreshed['access_token']);
    }
}
