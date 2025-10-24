<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_successfully(): void
    {
        $email = 'success.user@example.com';
        $password = 'Password123!';

        $user = User::factory()->create([
            'email' => $email,
            'status' => 1,
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson('/api/v1/user/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message.success.0', __('Login Successful'));
        $this->assertFalse($user->fresh()->two_factor_verified);
    }

    public function test_user_login_fails_with_invalid_credentials(): void
    {
        $email = 'failed.user@example.com';

        User::factory()->create([
            'email' => $email,
            'status' => 1,
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->postJson('/api/v1/user/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('message.error.0', __('Incorrect Password'));
    }

    public function test_user_login_is_rate_limited_after_exceeding_attempts(): void
    {
        $email = 'locked.user@example.com';

        User::factory()->create([
            'email' => $email,
            'status' => 1,
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $payload = [
            'email' => $email,
            'password' => 'invalid-password',
        ];

        $maxAttempts = (int) config('auth.rate_limits.user_login.max_attempts', 5);

        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->postJson('/api/v1/user/login', $payload);
        }

        $response = $this->postJson('/api/v1/user/login', $payload);

        $response->assertStatus(400);
        $this->assertStringContainsString('Too many login attempts', $response->json('message.error.0'));
        $this->assertGreaterThan(0, $response->json('data.retry_after_seconds'));

        RateLimiter::clear(strtolower($email) . '|127.0.0.1');
    }
}
