<?php

namespace App\Services\VirtualCard;

use App\Constants\GlobalConst;
use App\Models\StrowalletCustomerKyc;
use App\Models\StrowalletVirtualCard;
use App\Models\StrowalletVirtualCardAudit;
use App\Models\User;
use App\Models\VirtualCardApi;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StrowalletVirtualCardService implements VirtualCardProviderInterface, KycProviderInterface
{
    protected Client $client;
    protected VirtualCardApi $api;
    protected object $config;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->api = VirtualCardApi::firstOrFail();
        $this->config = (object) ($this->api->config ?? []);
    }

    public function storeKycMedia(User $user, ?UploadedFile $idDocument, ?UploadedFile $faceImage, ?Model $existing = null): array
    {
        $record = $existing instanceof StrowalletCustomerKyc
            ? $existing
            : StrowalletCustomerKyc::firstOrNew(['user_id' => $user->id]);

        $created = !$record->exists;

        $idFileName = $record->id_image;
        $faceFileName = $record->face_image;

        if ($idDocument) {
            $uploaded = upload_file($idDocument, 'card-kyc-images', $record->id_image ?? null);
            if (!$uploaded) {
                throw new RuntimeException(__('Failed to upload the identification document.'));
            }

            $idFileName = upload_files_from_path_dynamic([$uploaded['dev_path']], 'card-kyc-images', $record->id_image ?? null);
        }

        if ($faceImage) {
            $uploaded = upload_file($faceImage, 'card-kyc-images', $record->face_image ?? null);
            if (!$uploaded) {
                throw new RuntimeException(__('Failed to upload the facial verification image.'));
            }

            $faceFileName = upload_files_from_path_dynamic([$uploaded['dev_path']], 'card-kyc-images', $record->face_image ?? null);
        }

        if (!$idFileName || !$faceFileName) {
            throw new RuntimeException(__('KYC images are required.'));
        }

        $record->fill([
            'user_id' => $user->id,
            'id_image' => $idFileName,
            'face_image' => $faceFileName,
        ]);
        $record->save();

        return [
            'record' => $record,
            'encoded_id' => $this->encodeImage($idFileName),
            'encoded_face' => $this->encodeImage($faceFileName),
            'created' => $created,
        ];
    }

    public function createCustomer(User $user, array $payload, array $kycMedia): array
    {
        $formParams = [
            'public_key' => $this->publicKey(),
            'houseNumber' => Arr::get($payload, 'house_number'),
            'firstName' => Arr::get($payload, 'first_name'),
            'lastName' => Arr::get($payload, 'last_name'),
            'idNumber' => (string) random_int(123456789, 987654321),
            'customerEmail' => Arr::get($payload, 'customer_email'),
            'phoneNumber' => Arr::get($payload, 'phone', $user->full_mobile),
            'dateOfBirth' => Arr::get($payload, 'date_of_birth'),
            'idImage' => $kycMedia['encoded_id'] ?? '',
            'userPhoto' => $kycMedia['encoded_face'] ?? '',
            'line1' => Arr::get($payload, 'address'),
            'state' => $this->configValue('strowallet_state', $this->configValue('strowallet_city', 'Accra')),
            'zipCode' => Arr::get($payload, 'zip_code'),
            'city' => $this->configValue('strowallet_city', 'Accra'),
            'country' => $this->configValue('strowallet_country', 'Ghana'),
            'idType' => Arr::get($payload, 'id_type', 'PASSPORT'),
        ];

        try {
            $response = $this->client->request('POST', $this->buildUrl('create-user/'), [
                'headers' => [
                    'accept' => 'application/json',
                ],
                'form_params' => $formParams,
            ]);

            $result = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $exception) {
            Log::error('Strowallet create customer failed', ['exception' => $exception]);
            $this->rollbackKycOnFailure($kycMedia);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ];
        }

        if (isset($result['success']) && $result['success'] === true) {
            return [
                'status' => true,
                'message' => __('Create Customer Successfully.'),
                'data' => $result['response'] ?? [],
            ];
        }

        $this->rollbackKycOnFailure($kycMedia);

        return [
            'status' => false,
            'message' => $result['message'] ?? __('Something is wrong! Contact With Admin'),
            'data' => null,
        ];
    }

    public function updateCustomer(User $user, array $payload, array $kycMedia, array $context = []): array
    {
        $customer = $context['customer'] ?? null;

        if (!$customer || empty($customer->customerId)) {
            return [
                'status' => false,
                'message' => __('Customer record not found for update.'),
                'data' => null,
            ];
        }

        $query = http_build_query([
            'public_key' => $this->publicKey(),
            'customerId' => $customer->customerId,
            'firstName' => Arr::get($payload, 'first_name'),
            'lastName' => Arr::get($payload, 'last_name'),
            'idImage' => $kycMedia['encoded_id'] ?? '',
            'userPhoto' => $kycMedia['encoded_face'] ?? '',
        ]);

        try {
            $response = $this->client->request('PUT', $this->buildUrl('updateCardCustomer/?' . $query), [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]);

            $result = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $exception) {
            Log::error('Strowallet update customer failed', ['exception' => $exception]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ];
        }

        if (isset($result['success']) && $result['success'] === true) {
            return [
                'status' => true,
                'message' => __('Customer updated successfully'),
                'data' => $result['response'] ?? [],
            ];
        }

        return [
            'status' => false,
            'message' => $result['message'] ?? __('Something went wrong! Please try again.'),
            'data' => null,
        ];
    }

    public function getCustomer(string $customerId, string $customerEmail): array
    {
        $query = http_build_query([
            'public_key' => $this->publicKey(),
            'customerId' => $customerId,
            'customerEmail' => $customerEmail,
        ]);

        try {
            $response = $this->client->request('GET', $this->buildUrl('getcardholder/?' . $query), [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]);
            $result = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $exception) {
            Log::error('Strowallet get customer failed', ['exception' => $exception]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
                'data' => [],
            ];
        }

        if (isset($result['success']) && $result['success'] === true) {
            return [
                'status' => true,
                'message' => __('Customer Get SuccessFully'),
                'data' => $result['data'] ?? [],
            ];
        }

        return [
            'status' => false,
            'message' => $result['message'] ?? __('Something went wrong! Please try again.'),
            'data' => [],
        ];
    }

    public function createCard(User $user, float $amount, object $customer, array $formData): array
    {
        $payload = [
            'name_on_card' => Arr::get($formData, 'name_on_card', $user->username),
            'card_type' => 'visa',
            'public_key' => $this->publicKey(),
            'amount' => $amount,
            'customerEmail' => $customer->customerEmail ?? null,
            'developer_code' => $this->configValue('strowallet_developer_code', 'appdevsx'),
        ];

        if ($this->mode() === GlobalConst::SANDBOX) {
            $payload['mode'] = 'sandbox';
        }

        try {
            $response = $this->client->request('POST', $this->buildUrl('create-card/'), [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
                'body' => json_encode($payload),
            ]);

            $result = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $exception) {
            Log::error('Strowallet create card failed', ['exception' => $exception]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ];
        }

        if (isset($result['success']) && $result['success'] === true) {
            return [
                'status' => true,
                'message' => __('Create Card Successfully.'),
                'data' => $result['response'] ?? [],
            ];
        }

        if (isset($result['error'])) {
            return [
                'status' => false,
                'message' => __('Contact With Strowallet Account Administration, :message', ['message' => $result['error'] ?? '']),
                'data' => null,
            ];
        }

        return [
            'status' => false,
            'message' => __('Contact With Strowallet Account Administration, :message', ['message' => $result['message'] ?? '']),
            'data' => null,
        ];
    }

    public function getCardDetails(string $cardId): array
    {
        $payload = [
            'public_key' => $this->publicKey(),
            'card_id' => $cardId,
        ];

        if ($this->mode() === GlobalConst::SANDBOX) {
            $payload['mode'] = 'sandbox';
        }

        try {
            $response = $this->client->request('POST', $this->buildUrl('fetch-card-detail/'), [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
                'body' => json_encode($payload),
            ]);

            $result = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $exception) {
            Log::error('Strowallet fetch card details failed', ['exception' => $exception]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ];
        }

        if (isset($result['success']) && $result['success'] === true) {
            return [
                'status' => true,
                'message' => __('Card Details Retrieved Successfully.'),
                'data' => $result['response'] ?? [],
            ];
        }

        return [
            'status' => false,
            'message' => $result['message'] ?? __('Your Card Is Pending!Please Contact With Admin'),
            'data' => null,
        ];
    }

    public function syncCardFromRemote(Model $card, array $cardDetails, ?User $actor = null): Model
    {
        $cardDetail = Arr::get($cardDetails, 'card_detail', []);
        if (!$cardDetail) {
            return $card;
        }

        $cardStatusBefore = $card->card_status ?? null;

        $card->fill([
            'card_status' => Arr::get($cardDetail, 'card_status', $card->card_status),
            'card_number' => Arr::get($cardDetail, 'card_number', $card->card_number),
            'last4' => Arr::get($cardDetail, 'last4', $card->last4),
            'cvv' => Arr::get($cardDetail, 'cvv', $card->cvv),
            'expiry' => Arr::get($cardDetail, 'expiry', $card->expiry),
            'balance' => Arr::get($cardDetail, 'balance', $card->balance),
        ]);
        $card->save();

        if ($cardStatusBefore !== null && $cardStatusBefore !== $card->card_status) {
            $this->recordAudit($card, 'card_status', $cardStatusBefore, $card->card_status, $actor);
        }

        return $card;
    }

    public function getWalletBalance(string $currencyCode): array
    {
        $url = $this->buildUrl('wallet/balance/' . $currencyCode . '/?public_key=' . $this->publicKey());

        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]);
            $result = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $exception) {
            Log::error('Strowallet balance fetch failed', ['exception' => $exception]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
                'balance' => 0,
            ];
        }

        if (isset($result['balance'])) {
            return [
                'status' => true,
                'message' => __('SuccessFully Fetch Account Balance'),
                'balance' => (float) $result['balance'],
            ];
        }

        return [
            'status' => false,
            'message' => $result['message'] ?? '',
            'balance' => 0,
        ];
    }

    public function refreshCardBalance(Model $card, User $user): float
    {
        $response = $this->getCardDetails($card->card_id);

        if (!$response['status'] || empty($response['data'])) {
            return (float) ($card->balance ?? 0);
        }

        $updated = $this->syncCardFromRemote($card, $response['data'], $user);

        return (float) ($updated->balance ?? 0);
    }

    public function toggleCardStatus(Model $card, bool $shouldActivate, User $actor): array
    {
        if (!$card instanceof StrowalletVirtualCard) {
            throw new RuntimeException('Card instance is invalid for this provider.');
        }

        $action = $shouldActivate ? 'unfreeze' : 'freeze';
        $expectedStatus = $shouldActivate ? 1 : 0;

        $query = http_build_query([
            'action' => $action,
            'card_id' => $card->card_id,
            'public_key' => $this->publicKey(),
        ]);

        try {
            $response = $this->client->request('POST', $this->buildUrl('action/status/?' . $query), [
                'headers' => [
                    'accept' => 'application/json',
                ],
            ]);

            $result = json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $exception) {
            Log::error('Strowallet change card status failed', ['exception' => $exception]);

            return [
                'status' => false,
                'message' => $exception->getMessage(),
            ];
        }

        if (isset($result['status'])) {
            $previous = (int) $card->is_active;
            $card->is_active = $expectedStatus;
            $card->save();

            if ($previous !== $card->is_active) {
                $this->recordAudit($card, 'is_active', (string) $previous, (string) $card->is_active, $actor);
            }

            $message = $shouldActivate ? __('Card Unfreeze successfully') : __('Card Freeze successfully');

            return [
                'status' => true,
                'message' => $message,
            ];
        }

        return [
            'status' => false,
            'message' => $result['message'] ?? __('Something went wrong! Please try again.'),
        ];
    }

    protected function recordAudit(StrowalletVirtualCard $card, string $attribute, $oldValue, $newValue, ?User $actor = null): void
    {
        if ($oldValue == $newValue) {
            return;
        }

        try {
            StrowalletVirtualCardAudit::create([
                'strowallet_virtual_card_id' => $card->id,
                'changed_by' => $actor?->id,
                'attribute' => $attribute,
                'old_value' => is_bool($oldValue) ? ($oldValue ? '1' : '0') : (string) $oldValue,
                'new_value' => is_bool($newValue) ? ($newValue ? '1' : '0') : (string) $newValue,
            ]);
        } catch (Exception $exception) {
            Log::error('Failed to record Strowallet card audit', ['exception' => $exception]);
        }
    }

    protected function encodeImage(?string $fileName): ?string
    {
        if (!$fileName) {
            return null;
        }

        $path = $this->kycFilePath($fileName);
        if (!$path || !File::exists($path)) {
            return null;
        }

        try {
            return base64_encode(File::get($path));
        } catch (Exception $exception) {
            Log::error('Failed to encode KYC image', ['exception' => $exception, 'path' => $path]);
            return null;
        }
    }

    protected function kycFilePath(string $fileName): ?string
    {
        $filesPath = files_path('card-kyc-images')->path ?? null;
        if (!$filesPath) {
            return null;
        }

        return public_path($filesPath . '/' . ltrim($fileName, '/'));
    }

    protected function rollbackKycOnFailure(array $kycMedia): void
    {
        if (!empty($kycMedia['created']) && $kycMedia['created'] === true && isset($kycMedia['record']) && $kycMedia['record'] instanceof StrowalletCustomerKyc) {
            try {
                $kycMedia['record']->delete();
            } catch (Exception $exception) {
                Log::warning('Failed to rollback KYC record after unsuccessful request', ['exception' => $exception]);
            }
        }
    }

    protected function buildUrl(string $path): string
    {
        $base = rtrim((string) $this->configValue('strowallet_url'), '/');
        return $base . '/' . ltrim($path, '/');
    }

    protected function publicKey(): string
    {
        return (string) $this->configValue('strowallet_public_key');
    }

    protected function mode(): string
    {
        return (string) $this->configValue('strowallet_mode', GlobalConst::SANDBOX);
    }

    protected function configValue(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }
}
