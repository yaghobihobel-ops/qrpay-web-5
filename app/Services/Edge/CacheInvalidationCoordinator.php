<?php

namespace App\Services\Edge;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;
use Throwable;

class CacheInvalidationCoordinator
{
    public function __construct(
        private readonly EdgeCacheRepository $edgeCache,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function broadcast(string $scope, ?string $identifier = null, array $context = []): void
    {
        $payload = [
            'scope' => $scope,
            'identifier' => $identifier,
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->edgeCache->forget($scope, $identifier);

        try {
            Redis::publish($this->channelName(), json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (Throwable $exception) {
            $this->logger->warning('Edge cache invalidation publish failed', [
                'exception' => $exception->getMessage(),
                'scope' => $scope,
                'identifier' => $identifier,
            ]);
        }
    }

    public function broadcastSettings(?string $identifier = null, array $context = []): void
    {
        $this->broadcast(EdgeCacheRepository::SCOPE_SETTINGS, $identifier, $context);
    }

    public function broadcastBanks(?string $identifier = null, array $context = []): void
    {
        $this->broadcast(EdgeCacheRepository::SCOPE_BANKS, $identifier, $context);
    }

    public function broadcastRates(?string $identifier = null, array $context = []): void
    {
        $this->broadcast(EdgeCacheRepository::SCOPE_RATES, $identifier, $context);
    }

    public function handleMessage(string $message): void
    {
        $payload = json_decode($message, true);
        if (!is_array($payload) || empty($payload['scope'])) {
            return;
        }

        $scope = (string) $payload['scope'];
        $identifier = $payload['identifier'] ?? null;

        $this->edgeCache->forget($scope, $identifier);
    }

    public function subscribe(?callable $afterMessage = null): void
    {
        $channel = $this->channelName();

        Redis::subscribe([$channel], function (string $message) use ($afterMessage) {
            $this->handleMessage($message);

            if ($afterMessage) {
                $afterMessage($message);
            }
        });
    }

    public function channelName(): string
    {
        return (string) $this->config->get('edge.cache.invalidation_channel', 'edge-cache:invalidate');
    }
}
