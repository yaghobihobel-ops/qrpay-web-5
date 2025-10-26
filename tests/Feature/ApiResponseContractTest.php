<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ApiResponseContractTest extends TestCase
{
    public function test_success_macro_returns_standard_structure(): void
    {
        $response = TestResponse::fromBaseResponse(response()->success('All good.', ['value' => 123]));

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => 'All good.',
                'details' => ['value' => 123],
            ]);
    }

    public function test_error_macro_returns_standard_structure(): void
    {
        $response = TestResponse::fromBaseResponse(response()->error('Something went wrong.', ApiErrorCode::UNKNOWN, ['errors' => ['Failure']], 400));

        $response->assertStatus(400)
            ->assertJson([
                'code' => ApiErrorCode::UNKNOWN->value,
                'message' => 'Something went wrong.',
                'details' => ['errors' => ['Failure']],
            ]);
    }

    public function test_paginated_macro_returns_standard_structure(): void
    {
        $paginator = new LengthAwarePaginator([['id' => 1], ['id' => 2]], 10, 2, 1);
        $response = TestResponse::fromBaseResponse(response()->paginated($paginator, 'Fetched items.'));

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'message' => 'Fetched items.',
            ])
            ->assertJsonStructure([
                'details' => [
                    'data',
                    'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                ],
            ]);
    }

    public function test_validation_exception_returns_standard_error_structure(): void
    {
        Route::post('/testing/validation', function () {
            throw ValidationException::withMessages(['email' => ['The email field is required.']]);
        });

        $this->postJson('/testing/validation', [])->assertStatus(422)
            ->assertJson([
                'code' => ApiErrorCode::VALIDATION_ERROR->value,
                'message' => 'The given data was invalid.',
            ])
            ->assertJsonStructure(['details' => ['errors']]);
    }

    public function test_authentication_exception_returns_standard_error_structure(): void
    {
        Route::get('/testing/authentication', function () {
            throw new AuthenticationException();
        });

        $this->getJson('/testing/authentication')->assertStatus(401)
            ->assertJson([
                'code' => ApiErrorCode::AUTHENTICATION_ERROR->value,
                'message' => 'Unauthenticated.',
                'details' => null,
            ]);
    }

    public function test_authorization_exception_returns_standard_error_structure(): void
    {
        Route::get('/testing/authorization', function () {
            throw new AuthorizationException();
        });

        $this->getJson('/testing/authorization')->assertStatus(403)
            ->assertJson([
                'code' => ApiErrorCode::AUTHORIZATION_ERROR->value,
                'message' => 'This action is unauthorized.',
                'details' => null,
            ]);
    }

    public function test_not_found_exception_returns_standard_error_structure(): void
    {
        Route::get('/testing/not-found', function () {
            abort(404);
        });

        $this->getJson('/testing/not-found')->assertStatus(404)
            ->assertJson([
                'code' => ApiErrorCode::NOT_FOUND->value,
                'message' => 'The requested resource could not be found.',
            ]);
    }

    public function test_generic_exception_returns_standard_error_structure(): void
    {
        config(['app.debug' => false]);

        Route::get('/testing/error', function () {
            throw new \RuntimeException('Boom');
        });

        $this->getJson('/testing/error')->assertStatus(500)
            ->assertJson([
                'code' => ApiErrorCode::UNKNOWN->value,
                'message' => 'An unexpected error occurred.',
                'details' => null,
            ]);
    }
}
