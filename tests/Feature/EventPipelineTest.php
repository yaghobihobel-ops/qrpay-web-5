<?php

namespace Tests\Feature;

use App\Services\Analytics\Clients\AnalyticsClientInterface;
use App\Services\Analytics\EventPipeline;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class EventPipelineTest extends TestCase
{
    public function test_it_enriches_payload_before_ingest(): void
    {
        $client = Mockery::mock(AnalyticsClientInterface::class);
        $client->shouldReceive('ingest')->once()->with(Mockery::on(function ($payload) {
            return isset($payload['event_id'], $payload['ingested_at'], $payload['source'])
                && $payload['event_name'] === 'transaction.completed';
        }));

        $pipeline = new EventPipeline($client);
        $pipeline->ingest(['event_name' => 'transaction.completed']);
    }

    public function test_it_buffers_when_client_fails(): void
    {
        Config::set('analytics.buffer_path', storage_path('app/testing-buffer.ndjson'));
        Storage::fake('local');

        $client = Mockery::mock(AnalyticsClientInterface::class);
        $client->shouldReceive('ingest')->andThrow(new \RuntimeException('fail'));

        $pipeline = new EventPipeline($client);
        $pipeline->ingest(['event_name' => 'transaction.completed']);

        Storage::disk('local')->assertExists('testing-buffer.ndjson');
    }
}
