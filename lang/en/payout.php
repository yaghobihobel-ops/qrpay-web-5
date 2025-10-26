<?php

return [
    'amount_below_minimum' => 'The requested amount is below the minimum limit for :country payouts (:min).',
    'amount_above_maximum' => 'The requested amount exceeds the maximum limit for :country payouts (:max).',
    'missing_bank_code' => 'A valid bank code is required for this transfer.',
    'bank_not_supported' => 'The selected bank (:bank) is not supported for this payout route.',
    'bank_found' => 'Bank details verified successfully.',
    'transfer_created' => 'The payout request has been created successfully and queued for processing.',
    'status_pending' => 'The payout is pending compliance review.',
    'missing_iban' => 'An IBAN is required for Turkish payouts.',
    'status_in_review' => 'The payout is currently in manual review.',
    'missing_swift_code' => 'A SWIFT/BIC code is required for this payout.',
    'status_submitted' => 'The payout has been submitted to the correspondent bank.',
    'token_not_supported' => 'The selected token (:token) is not supported for Iran crypto payouts.',
    'network_not_supported' => 'The requested blockchain network (:network) is not available.',
    'network_found' => 'The blockchain network is available for this payout.',
    'missing_wallet_address' => 'A destination wallet address is required.',
    'status_onchain_confirmation' => 'Awaiting on-chain confirmations.',
    'compliance_blocked' => 'Compliance checks prevented the payout: :reason',
    'payout_success_message' => 'Withdrawal request submitted successfully. Reference: :reference',
];
