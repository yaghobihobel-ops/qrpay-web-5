<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiHandler
{
    public function render(Request $request, Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return response()->error(
                __('The given data was invalid.'),
                ApiErrorCode::VALIDATION_ERROR,
                ['errors' => $exception->errors()],
                422
            );
        }

        if ($exception instanceof AuthenticationException) {
            return response()->error(
                __('Unauthenticated.'),
                ApiErrorCode::AUTHENTICATION_ERROR,
                null,
                401
            );
        }

        if ($exception instanceof AuthorizationException) {
            return response()->error(
                __('This action is unauthorized.'),
                ApiErrorCode::AUTHORIZATION_ERROR,
                null,
                403
            );
        }

        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return response()->error(
                __('The requested resource could not be found.'),
                ApiErrorCode::NOT_FOUND,
                null,
                404
            );
        }

        if ($exception instanceof HttpExceptionInterface) {
            return response()->error(
                $exception->getMessage() ?: __('Request failed.'),
                $this->resolveHttpErrorCode($exception->getStatusCode()),
                null,
                $exception->getStatusCode()
            );
        }

        $details = null;

        if (config('app.debug')) {
            $details = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }

        return response()->error(
            __('An unexpected error occurred.'),
            ApiErrorCode::UNKNOWN,
            $details,
            500
        );
    }

    protected function resolveHttpErrorCode(int $status): ApiErrorCode
    {
        return match ($status) {
            400 => ApiErrorCode::UNKNOWN,
            401 => ApiErrorCode::AUTHENTICATION_ERROR,
            403 => ApiErrorCode::AUTHORIZATION_ERROR,
            404 => ApiErrorCode::NOT_FOUND,
            default => ApiErrorCode::UNKNOWN,
        };
    }
}
