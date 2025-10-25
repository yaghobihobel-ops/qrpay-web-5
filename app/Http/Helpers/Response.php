<?php

namespace App\Http\Helpers;

use App\Enums\ApiErrorCode;
use Illuminate\Support\Arr;
use Illuminate\Support\MessageBag;

class Response
{
    public static function error($errors, $data = null, $status = 400)
    {
        [$message, $messages] = self::normalizeMessages($errors, __('Request failed.'));

        $details = ['errors' => $messages];

        if (!is_null($data)) {
            $details['data'] = $data;
        }

        return response()->error($message, ApiErrorCode::UNKNOWN, $details, $status);
    }

    public static function success($success, $data = null, $status = 200)
    {
        [$message, $messages] = self::normalizeMessages($success, __('Request succeeded.'));

        $details = ['messages' => $messages];

        if (!is_null($data)) {
            $details['data'] = $data;
        }

        return response()->success($message, $details, $status);
    }

    public static function warning($warning, $data = null, $status = 400)
    {
        [$message, $messages] = self::normalizeMessages($warning, __('Request warning.'));

        $details = ['warnings' => $messages];

        if (!is_null($data)) {
            $details['data'] = $data;
        }

        return response()->error($message, ApiErrorCode::UNKNOWN, $details, $status);
    }

    public static function paymentApiError($errors, $data = [], $status = 400)
    {
        [$message, $messages] = self::normalizeMessages($errors, __('Request failed.'));

        $details = ['errors' => $messages];

        if (!empty($data)) {
            $details['data'] = $data;
        }

        return response()->error($message, ApiErrorCode::UNKNOWN, $details, $status);
    }

    public static function paymentApiSuccess($success, $data = null, $status = 200)
    {
        [$message, $messages] = self::normalizeMessages($success, __('Request succeeded.'));

        $details = ['messages' => $messages];

        if (!is_null($data)) {
            $details['data'] = $data;
        }

        return response()->success($message, $details, $status);
    }

    protected static function normalizeMessages($input, string $default): array
    {
        if ($input instanceof MessageBag) {
            $messages = $input->all();
        } elseif (is_array($input)) {
            $messages = Arr::flatten($input);
        } elseif (is_string($input)) {
            $messages = [$input];
        } elseif (is_null($input)) {
            $messages = [];
        } else {
            $messages = [(string) $input];
        }

        $message = $messages[0] ?? $default;

        return [$message, $messages];
    }
}
