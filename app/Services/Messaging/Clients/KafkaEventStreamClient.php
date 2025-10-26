<?php

namespace App\Services\Messaging\Clients;

use App\Services\Messaging\EventStreamClient;
use Illuminate\Support\Facades\Log;

class KafkaEventStreamClient implements EventStreamClient
{
    public function __construct(protected array $config = [])
    {
    }

    public function publish(string $destination, string $payload, array $headers = []): void
    {
        if (!class_exists(\RdKafka\Conf::class)) {
            Log::warning('Kafka extension is not installed. Falling back to log sink.', [
                'destination' => $destination,
            ]);

            (new LoggingEventStreamClient($this->config))->publish($destination, $payload, $headers);
            return;
        }

        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', $this->config['brokers'] ?? 'localhost:9092');

        if (!empty($this->config['security_protocol'])) {
            $conf->set('security.protocol', $this->config['security_protocol']);
        }

        if (!empty($this->config['sasl']['mechanism'])) {
            $conf->set('sasl.mechanisms', $this->config['sasl']['mechanism']);
            $conf->set('sasl.username', $this->config['sasl']['username'] ?? '');
            $conf->set('sasl.password', $this->config['sasl']['password'] ?? '');
        }

        foreach ($this->config['options'] ?? [] as $key => $value) {
            if ($value === null) {
                continue;
            }
            $conf->set($key, (string) $value);
        }

        $producer = new \RdKafka\Producer($conf);
        $topic = $producer->newTopic($destination);

        $partition = defined('RD_KAFKA_PARTITION_UA') ? constant('RD_KAFKA_PARTITION_UA') : 0;

        $topic->produce($partition, 0, $payload);
        $producer->poll(0);

        $result = $producer->flush(2000);
        $successCode = defined('RD_KAFKA_RESP_ERR_NO_ERROR') ? constant('RD_KAFKA_RESP_ERR_NO_ERROR') : 0;

        if ($successCode !== $result) {
            Log::error('Kafka producer failed to flush messages', [
                'destination' => $destination,
                'result' => $result,
            ]);
        }
    }
}
