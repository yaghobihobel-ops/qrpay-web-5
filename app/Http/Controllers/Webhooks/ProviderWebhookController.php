<?php

namespace App\Http\Controllers\Webhooks;

use App\Support\Providers\CountryProviderResolver;
use App\Support\Providers\ProviderChannelMapper;
use App\Support\Reconciliation\ReconciliationRecorder;
use App\Support\Webhooks\WebhookSignatureValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProviderWebhookController extends Controller
{
    public function __construct(
        protected CountryProviderResolver $providerResolver,
        protected ProviderChannelMapper $channelMapper,
        protected WebhookSignatureValidator $signatureValidator,
        protected ReconciliationRecorder $recorder,
    ) {
    }

    public function handle(Request $request, string $country, string $channel, string $providerKey): JsonResponse
    {
        $country = strtoupper($country);
        $channel = strtolower($channel);
        $providerKey = Str::lower($providerKey);

        $contract = $this->channelMapper->contractFor($channel);

        if (! $contract) {
            return response()->json([
                'acknowledged' => false,
                'reason' => 'unknown-channel',
            ], 422);
        }

        $providerClass = $this->providerResolver->classFor($contract, $country);

        if (! $providerClass) {
            return response()->json([
                'acknowledged' => false,
                'reason' => 'provider-unavailable',
            ], 404);
        }

        $rawBody = $request->getContent();
        $headers = $request->headers->all();

        $validation = $this->signatureValidator->validate(
            $country,
            $channel,
            $providerKey,
            $headers,
            $rawBody
        );

        $idempotencyHeader = config('webhooks.idempotency_header', 'X-Idempotency-Key');
        $idempotencyKey = $request->header($idempotencyHeader)
            ?? Arr::get($request->all(), 'idempotency_key')
            ?? hash('sha256', $providerKey . '|' . $rawBody);

        $occurredAtHeader = config('webhooks.timestamp_header', 'X-Signature-Timestamp');
        $occurredAt = $request->header($occurredAtHeader);

        $payload = $request->all();

        $event = $this->recorder->record([
            'country_code' => $country,
            'channel' => $channel,
            'provider_key' => $providerKey,
            'provider_class' => $providerClass,
            'event_type' => Arr::get($payload, 'event') ?? Arr::get($payload, 'type'),
            'provider_reference' => Arr::get($payload, 'reference')
                ?? Arr::get($payload, 'id')
                ?? Arr::get($payload, 'data.id'),
            'status' => Arr::get($payload, 'status', 'RECEIVED'),
            'signature_valid' => $validation->valid,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
            'headers' => array_map(fn ($values) => $values[0] ?? null, $headers),
            'validation_details' => [
                'provided' => $validation->providedSignature,
                'expected' => $validation->expectedSignature,
                'algorithm' => $validation->algorithm,
                'reason' => $validation->reason,
            ],
            'occurred_at' => $occurredAt ? Carbon::parse($occurredAt) : null,
        ]);

        return response()->json([
            'acknowledged' => true,
            'signature_valid' => $validation->valid,
            'event_id' => $event->id,
        ]);
    }
}
