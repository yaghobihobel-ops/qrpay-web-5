<?php

namespace App\Contracts;

interface TopupProviderInterface
{
    /**
     * Detect the operator for the given phone number and ISO country code.
     *
     * @param string $phone
     * @param string $iso
     * @return array
     */
    public function detectOperator(string $phone, string $iso): array;

    /**
     * Execute a top up transaction with the provided payload.
     *
     * @param array $data
     * @return array
     */
    public function makeTopUp(array $data): array;

    /**
     * Retrieve a transaction report/details from the provider.
     *
     * @param string $transactionId
     * @return array
     */
    public function getTransaction(string $transactionId): array;
}
