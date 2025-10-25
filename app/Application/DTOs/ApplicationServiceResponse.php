<?php

namespace App\Application\DTOs;

use App\Http\Helpers\Api\Helpers;
use Illuminate\Http\JsonResponse;

class ApplicationServiceResponse
{
    public function __construct(
        public readonly string $status,
        public readonly array $messages = [],
        public readonly mixed $data = null,
        protected $responseCustomizer = null
    ) {
    }

    public static function success(mixed $data = null, array $messages = []): self
    {
        return new self('success', $messages, $data);
    }

    public static function error(array $messages = []): self
    {
        return new self('error', $messages);
    }

    public static function validation(array $messages = []): self
    {
        return new self('validation', $messages);
    }

    public function withResponse(callable $callback): self
    {
        $this->responseCustomizer = $callback;

        return $this;
    }

    public function toResponse(): JsonResponse
    {
        $response = match ($this->status) {
            'success' => Helpers::success($this->data, $this->messages),
            'validation' => Helpers::validation($this->messages),
            default => Helpers::error($this->messages),
        };

        if ($this->responseCustomizer) {
            $customized = ($this->responseCustomizer)($response);

            if ($customized instanceof JsonResponse) {
                $response = $customized;
            }
        }

        return $response;
    }
}
