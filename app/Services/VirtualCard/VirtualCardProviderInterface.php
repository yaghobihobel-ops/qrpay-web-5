<?php

namespace App\Services\VirtualCard;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface VirtualCardProviderInterface
{
    /**
     * Create a new virtual card for the provided customer context.
     *
     * @param  \App\Models\User  $user
     * @param  float  $amount
     * @param  object  $customer
     * @param  array  $formData
     * @return array{status: bool, message: string, data: mixed}
     */
    public function createCard(User $user, float $amount, object $customer, array $formData): array;

    /**
     * Fetch the remote card details by card identifier.
     *
     * @param  string  $cardId
     * @return array{status: bool, message: string, data: mixed}
     */
    public function getCardDetails(string $cardId): array;

    /**
     * Synchronise the stored card with the remote payload and record audit logs where applicable.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $card
     * @param  array  $cardDetails
     * @param  \App\Models\User|null  $actor
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function syncCardFromRemote(Model $card, array $cardDetails, ?User $actor = null): Model;

    /**
     * Retrieve the remote provider wallet balance for the supplied currency code.
     *
     * @param  string  $currencyCode
     * @return array{status: bool, message: string, balance: float|int}
     */
    public function getWalletBalance(string $currencyCode): array;

    /**
     * Refresh the stored balance for the provided card.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $card
     * @param  \App\Models\User  $user
     * @return float
     */
    public function refreshCardBalance(Model $card, User $user): float;

    /**
     * Change the card activation status remotely and persist an audit trail.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $card
     * @param  bool  $shouldActivate
     * @param  \App\Models\User  $actor
     * @return array{status: bool, message: string}
     */
    public function toggleCardStatus(Model $card, bool $shouldActivate, User $actor): array;
}
