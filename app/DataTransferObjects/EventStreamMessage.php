<?php

namespace App\DataTransferObjects;

use Carbon\CarbonInterface;
use Carbon\Factory as CarbonFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonException;

class EventStreamMessage implements Arrayable, Jsonable, \JsonSerializable
{
    public const DEFAULT_CONTENT_TYPE = 'application/json';

    protected string $eventId;

    protected CarbonInterface $occurredAt;

    protected CarbonInterface $recordedAt;

    protected string $producer;

    /**
     * @param  string  $eventType
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $context
     * @param  string|null  $destination
     * @param  string|null  $correlationId
     * @param  CarbonInterface|null  $occurredAt
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        protected string $eventType,
        protected array $payload,
        protected array $context = [],
        protected ?string $destination = null,
        protected ?string $correlationId = null,
        ?CarbonInterface $occurredAt = null,
        protected array $meta = [],
        protected array $headers = []
    ) {
        $factory = new CarbonFactory();
        $this->eventId = Str::uuid()->toString();
        $this->occurredAt = $occurredAt ?? $factory->now();
        $this->recordedAt = $factory->now();
        $this->producer = config('app.name', 'qrpay-web');
    }

    public static function forTransaction(
        \App\Models\Transaction $transaction,
        string $eventType,
        ?string $destination = null
    ): self {
        $currency = $transaction->currency;
        $customer = self::resolveCustomer($transaction);

        $details = $transaction->details;
        $source = null;
        $loyaltySegment = null;

        if ($details !== null) {
            $source = data_get($details, 'channel');
            $loyaltySegment = data_get($details, 'loyalty_segment');
        }

        $payload = [
            'transaction' => [
                'id' => $transaction->id,
                'trx_id' => $transaction->trx_id,
                'type' => $transaction->type,
                'attribute' => $transaction->attribute,
                'status' => $transaction->status,
                'status_label' => $transaction->stringStatus->value ?? null,
                'amount' => (float) $transaction->request_amount,
                'payable' => (float) $transaction->payable,
                'charge' => $transaction->charge?->total_charge,
                'currency' => [
                    'code' => $currency->currency_code ?? null,
                    'symbol' => $currency->currency_symbol ?? null,
                    'rate' => $currency->rate ?? null,
                    'name' => $currency->name ?? null,
                ],
                'details' => self::normalize($transaction->details),
                'created_at' => optional($transaction->created_at)?->toIso8601String(),
                'updated_at' => optional($transaction->updated_at)?->toIso8601String(),
            ],
            'customer' => $customer,
        ];

        $context = [
            'tenant' => config('app.name'),
            'environment' => config('app.env'),
            'locale' => app()->getLocale(),
            'source' => $source ?? $transaction->remark ?? 'web',
            'loyalty_segment' => $loyaltySegment,
        ];

        $meta = [
            'schema_version' => config('eventstream.schema_version', '1.0.0'),
            'tags' => array_values(array_filter([
                'transaction',
                Str::slug($transaction->type ?? 'unknown'),
                $currency->currency_code ?? null,
                $customer['type'] ?? null,
            ])),
            'priority' => self::resolvePriority($transaction->status),
        ];

        $headers = [
            'content_type' => self::DEFAULT_CONTENT_TYPE,
            'event_type' => $eventType,
            'schema_version' => $meta['schema_version'],
        ];

        return new self(
            $eventType,
            $payload,
            $context,
            $destination,
            $transaction->trx_id,
            optional($transaction->created_at),
            $meta,
            $headers
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => $this->eventType,
            'schema_version' => $this->meta['schema_version'] ?? config('eventstream.schema_version', '1.0.0'),
            'occurred_at' => $this->occurredAt->toIso8601String(),
            'recorded_at' => $this->recordedAt->toIso8601String(),
            'producer' => $this->producer,
            'correlation_id' => $this->correlationId,
            'context' => $this->context,
            'payload' => $this->payload,
            'meta' => Arr::except($this->meta, ['schema_version']),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function destination(): ?string
    {
        return $this->destination;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    /**
     * @return array{destination: string, body: string, headers: array<string, string|null>}
     */
    public function toTransportPayload(): array
    {
        $destination = $this->destination ?? config('eventstream.default_destination');

        try {
            $body = json_encode($this->toArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $payload = $this->toArray();
            $payload['meta']['encoding_error'] = $exception->getMessage();
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        }

        return [
            'destination' => $destination,
            'body' => $body,
            'headers' => $this->headers(),
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected static function resolvePriority(?int $status): string
    {
        return match ($status) {
            1 => 'high',
            2, 3 => 'medium',
            default => 'low',
        };
    }

    /**
     * @return array<string, mixed|null>
     */
    protected static function resolveCustomer(\App\Models\Transaction $transaction): array
    {
        if ($transaction->user_id) {
            return [
                'type' => 'user',
                'id' => $transaction->user_id,
                'name' => optional($transaction->user)->fullname ?? optional($transaction->user)->username,
                'email' => optional($transaction->user)->email,
            ];
        }

        if ($transaction->agent_id) {
            return [
                'type' => 'agent',
                'id' => $transaction->agent_id,
                'name' => optional($transaction->agent)->fullname ?? optional($transaction->agent)->username,
                'email' => optional($transaction->agent)->email,
            ];
        }

        if ($transaction->merchant_id) {
            return [
                'type' => 'merchant',
                'id' => $transaction->merchant_id,
                'name' => optional($transaction->merchant)->fullname ?? optional($transaction->merchant)->username,
                'email' => optional($transaction->merchant)->email,
            ];
        }

        return [
            'type' => 'unknown',
            'id' => null,
            'name' => null,
            'email' => null,
        ];
    }

    /**
     * @param  mixed  $value
     * @return array<string, mixed>|null
     */
    protected static function normalize(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        return ['value' => $value];
    }
}
