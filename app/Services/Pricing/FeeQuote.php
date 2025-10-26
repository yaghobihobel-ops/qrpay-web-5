<?php

namespace App\Services\Pricing;

class FeeQuote
{
    public function __construct(
        protected float $amount,
        protected string $transactionCurrency,
        protected float $feeAmount,
        protected string $feeCurrency,
        protected float $exchangeRate,
        protected string $feeType,
        protected ?int $ruleId = null,
        protected ?int $tierId = null,
        protected array $meta = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (float) ($data['amount'] ?? 0),
            transactionCurrency: strtoupper((string) ($data['transaction_currency'] ?? 'USD')),
            feeAmount: (float) ($data['fee_amount'] ?? 0),
            feeCurrency: strtoupper((string) ($data['fee_currency'] ?? 'USD')),
            exchangeRate: (float) ($data['exchange_rate'] ?? 1),
            feeType: (string) ($data['fee_type'] ?? 'flat'),
            ruleId: $data['rule_id'] ?? null,
            tierId: $data['tier_id'] ?? null,
            meta: $data['meta'] ?? []
        );
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getTransactionCurrency(): string
    {
        return $this->transactionCurrency;
    }

    public function getFeeAmount(): float
    {
        return $this->feeAmount;
    }

    public function getFeeCurrency(): string
    {
        return $this->feeCurrency;
    }

    public function getExchangeRate(): float
    {
        return $this->exchangeRate;
    }

    public function getFeeType(): string
    {
        return $this->feeType;
    }

    public function getRuleId(): ?int
    {
        return $this->ruleId;
    }

    public function getTierId(): ?int
    {
        return $this->tierId;
    }

    public function getMeta(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->meta;
        }

        return $this->meta[$key] ?? $default;
    }

    public function getConvertedFee(): float
    {
        if ($this->transactionCurrency === $this->feeCurrency) {
            return $this->feeAmount;
        }

        return $this->feeAmount * $this->exchangeRate;
    }

    public function getNetAmount(): float
    {
        $net = $this->amount - $this->getConvertedFee();

        return $net < 0 ? 0.0 : $net;
    }

    public function withMeta(string $key, $value): self
    {
        $clone = clone $this;
        $clone->meta[$key] = $value;

        return $clone;
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'transaction_currency' => $this->transactionCurrency,
            'fee_amount' => $this->feeAmount,
            'fee_currency' => $this->feeCurrency,
            'exchange_rate' => $this->exchangeRate,
            'converted_fee' => $this->getConvertedFee(),
            'net_amount' => $this->getNetAmount(),
            'fee_type' => $this->feeType,
            'rule_id' => $this->ruleId,
            'tier_id' => $this->tierId,
            'meta' => $this->meta,
        ];
    }
}
