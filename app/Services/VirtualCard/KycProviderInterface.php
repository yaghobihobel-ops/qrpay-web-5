<?php

namespace App\Services\VirtualCard;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

interface KycProviderInterface
{
    /**
     * Persist KYC media for the provided user and return the stored reference along with encoded payloads.
     *
     * @param  \App\Models\User  $user
     * @param  \Illuminate\Http\UploadedFile|null  $idDocument
     * @param  \Illuminate\Http\UploadedFile|null  $faceImage
     * @param  \Illuminate\Database\Eloquent\Model|null  $existing
     * @return array{record: Model, encoded_id: string|null, encoded_face: string|null, created: bool}
     */
    public function storeKycMedia(User $user, ?UploadedFile $idDocument, ?UploadedFile $faceImage, ?Model $existing = null): array;

    /**
     * Create a remote customer profile with the prepared payload.
     *
     * @param  \App\Models\User  $user
     * @param  array  $payload
     * @param  array  $kycMedia
     * @return array{status: bool, message: string, data: mixed}
     */
    public function createCustomer(User $user, array $payload, array $kycMedia): array;

    /**
     * Update the remote customer profile with refreshed payload and media.
     *
     * @param  \App\Models\User  $user
     * @param  array  $payload
     * @param  array  $kycMedia
     * @param  array  $context
     * @return array{status: bool, message: string, data: mixed}
     */
    public function updateCustomer(User $user, array $payload, array $kycMedia, array $context = []): array;

    /**
     * Retrieve the latest customer information from the remote provider.
     *
     * @param  string  $customerId
     * @param  string  $customerEmail
     * @return array{status: bool, message: string, data: mixed}
     */
    public function getCustomer(string $customerId, string $customerEmail): array;
}
