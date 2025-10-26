<?php

namespace App\Providers;

use App\Observers\TransactionEventObserver;
use App\Services\Messaging\Clients\KafkaEventStreamClient;
use App\Services\Messaging\Clients\LoggingEventStreamClient;
use App\Services\Messaging\Clients\NatsEventStreamClient;
use App\Services\Messaging\EventStreamClient;
use App\Services\Messaging\EventStreamPublisher;
use App\Models\Transaction;
use Illuminate\Support\ServiceProvider;

class EventStreamServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(EventStreamClient::class, function ($app) {
            $config = $app['config']->get('eventstream', []);
            return match ($config['driver'] ?? 'log') {
                'kafka' => new KafkaEventStreamClient($config['kafka'] ?? []),
                'nats' => new NatsEventStreamClient($config['nats'] ?? []),
                default => new LoggingEventStreamClient($config['log'] ?? []),
            };
        });

        $this->app->singleton(EventStreamPublisher::class, function ($app) {
            return new EventStreamPublisher(
                $app->make(EventStreamClient::class),
                $app['config']->get('eventstream', [])
            );
        });
    }

    public function boot()
    {
        Transaction::observe(TransactionEventObserver::class);
    }
}
