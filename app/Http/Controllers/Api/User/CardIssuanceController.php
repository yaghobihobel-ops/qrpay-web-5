<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Api\Helpers;
use App\Support\Cards\CardIssuanceManager;
use App\Support\Cards\Exceptions\CardIssuerNotConfiguredException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Throwable;

class CardIssuanceController extends Controller
{
    public function __construct(protected CardIssuanceManager $manager)
    {
    }

    public function issue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => ['required', 'string', 'max:5'],
            'type' => ['required', 'in:VIRTUAL,PHYSICAL'],
            'kyc_tier' => ['nullable', 'string', 'max:100'],
            'limits' => ['nullable', 'array'],
            'limits.daily' => ['nullable', 'numeric'],
            'limits.monthly' => ['nullable', 'numeric'],
            'limits.per_transaction' => ['nullable', 'numeric'],
            'metadata' => ['nullable', 'array'],
            'customer_reference' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];

            return Helpers::validation($error);
        }

        $data = $validator->validated();
        $country = strtoupper($data['country']);
        $type = strtoupper($data['type']);

        $payload = $this->payload($request, ['country', 'type']);
        $payload['user_id'] = $request->user()?->id;
        $payload['kyc_tier'] = $data['kyc_tier'] ?? null;
        $payload['limits'] = $data['limits'] ?? [];
        $payload['country'] = $country;

        if ($payload['kyc_tier'] === null) {
            unset($payload['kyc_tier']);
        }

        try {
            $result = $this->manager->issue($type, $country, $payload);
        } catch (CardIssuerNotConfiguredException|InvalidArgumentException $exception) {
            $error = ['error' => [$exception->getMessage()]];

            return Helpers::error($error);
        } catch (Throwable $exception) {
            report($exception);

            $error = ['error' => [__('Failed to issue card. Please try again later.')]];

            return Helpers::error($error);
        }

        $response = [
            'country' => $country,
            'type' => $type,
            'card' => $result,
        ];

        $message = ['success' => [__('Card issue request processed successfully.')]];

        return Helpers::success($response, $message);
    }

    public function activate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => ['required', 'string', 'max:5'],
            'card_id' => ['required', 'string', 'max:255'],
            'activation_code' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];

            return Helpers::validation($error);
        }

        $data = $validator->validated();
        $country = strtoupper($data['country']);

        $payload = $this->payload($request, ['country']);
        $payload['user_id'] = $request->user()?->id;
        $payload['country'] = $country;

        try {
            $result = $this->manager->activate($country, $payload);
        } catch (CardIssuerNotConfiguredException $exception) {
            $error = ['error' => [$exception->getMessage()]];

            return Helpers::error($error);
        } catch (Throwable $exception) {
            report($exception);

            $error = ['error' => [__('Failed to activate card. Please try again later.')]];

            return Helpers::error($error);
        }

        $response = [
            'country' => $country,
            'card' => $result,
        ];

        $message = ['success' => [__('Card activation processed successfully.')]];

        return Helpers::success($response, $message);
    }

    public function block(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => ['required', 'string', 'max:5'],
            'card_id' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
            'permanent' => ['sometimes', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];

            return Helpers::validation($error);
        }

        $data = $validator->validated();
        $country = strtoupper($data['country']);

        $payload = $this->payload($request, ['country']);
        $payload['user_id'] = $request->user()?->id;
        $payload['permanent'] = (bool) ($data['permanent'] ?? false);
        $payload['country'] = $country;

        try {
            $result = $this->manager->block($country, $payload);
        } catch (CardIssuerNotConfiguredException $exception) {
            $error = ['error' => [$exception->getMessage()]];

            return Helpers::error($error);
        } catch (Throwable $exception) {
            report($exception);

            $error = ['error' => [__('Failed to block card. Please try again later.')]];

            return Helpers::error($error);
        }

        $response = [
            'country' => $country,
            'card' => $result,
        ];

        $message = ['success' => [__('Card block request processed successfully.')]];

        return Helpers::success($response, $message);
    }

    public function limits(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country' => ['required', 'string', 'max:5'],
            'card_id' => ['required', 'string', 'max:255'],
            'limits' => ['required', 'array'],
            'limits.daily' => ['nullable', 'numeric'],
            'limits.monthly' => ['nullable', 'numeric'],
            'limits.per_transaction' => ['nullable', 'numeric'],
        ]);

        if ($validator->fails()) {
            $error = ['error' => $validator->errors()->all()];

            return Helpers::validation($error);
        }

        $data = $validator->validated();
        $country = strtoupper($data['country']);

        $payload = $this->payload($request, ['country']);
        $payload['user_id'] = $request->user()?->id;
        $payload['country'] = $country;

        try {
            $result = $this->manager->limits($country, $payload);
        } catch (CardIssuerNotConfiguredException $exception) {
            $error = ['error' => [$exception->getMessage()]];

            return Helpers::error($error);
        } catch (Throwable $exception) {
            report($exception);

            $error = ['error' => [__('Failed to update limits. Please try again later.')]];

            return Helpers::error($error);
        }

        $response = [
            'country' => $country,
            'card' => $result,
        ];

        $message = ['success' => [__('Card limits updated successfully.')]];

        return Helpers::success($response, $message);
    }

    protected function payload(Request $request, array $exclude = []): array
    {
        return Arr::except($request->all(), $exclude);
    }
}
