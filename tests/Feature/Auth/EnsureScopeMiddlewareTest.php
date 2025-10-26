<?php

namespace Tests\Feature\Auth;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EnsureScopeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['ensure.scope:scope:payments.manage,role:finance-admin,permission:transactions.approve'])
            ->get('/ensure-scope-test', function () {
                return response()->json(['ok' => true]);
            });

        $clientRepository = $this->app->make(ClientRepository::class);
        $clientRepository->createPersonalAccessClient(null, 'Test Personal Access Client', config('app.url', 'http://localhost'));
    }

    public function test_passport_token_with_required_scope_role_and_permission_is_allowed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('finance-admin'));
        $user->assignPermission(Permission::findOrCreate('transactions.approve'));

        Passport::actingAs($user, ['payments.manage']);

        $this->getJson('/ensure-scope-test')->assertOk()->assertJson(['ok' => true]);
    }

    public function test_request_missing_scope_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('finance-admin'));
        $user->assignPermission(Permission::findOrCreate('transactions.approve'));

        Passport::actingAs($user, ['transactions.view']);

        $this->getJson('/ensure-scope-test')->assertStatus(403);
    }

    public function test_request_missing_permission_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('finance-admin'));

        Passport::actingAs($user, ['payments.manage']);

        $this->getJson('/ensure-scope-test')->assertStatus(403);
    }

    public function test_jwt_with_matching_claims_is_allowed(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('finance-admin'));
        $user->assignPermission(Permission::findOrCreate('transactions.approve'));

        $token = $this->app->make(TokenService::class)->issueJwtToken($user, [
            'scopes' => ['payments.manage'],
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token['access_token'])
            ->getJson('/ensure-scope-test')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_revoked_jwt_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('finance-admin'));
        $user->assignPermission(Permission::findOrCreate('transactions.approve'));

        $service = $this->app->make(TokenService::class);
        $token = $service->issueJwtToken($user, ['scopes' => ['payments.manage']]);
        $service->revokeJwtToken($token['access_token']);

        $this->withHeader('Authorization', 'Bearer ' . $token['access_token'])
            ->getJson('/ensure-scope-test')
            ->assertStatus(401);
    }
}
