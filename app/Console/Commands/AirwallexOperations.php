<?php

namespace App\Console\Commands;

use App\Services\Airwallex\AirwallexClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class AirwallexOperations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'airwallex {action : token|cardholders|create-cardholder}
                            {--payload= : Path to a JSON file containing the request body for create-cardholder}
                            {--token= : Pre-generated bearer token to reuse for cardholder operations}
                            {--filters= : JSON encoded filters for listing cardholders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute Airwallex operational tasks without exposing public routes.';

    public function __construct(private readonly AirwallexClient $client)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'token' => $this->handleToken(),
            'cardholders' => $this->handleListCardholders(),
            'create-cardholder' => $this->handleCreateCardholder(),
            default => $this->invalidAction(),
        };
    }

    private function handleToken(): int
    {
        $response = $this->client->authenticate();
        $this->line(json_encode($response, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function handleListCardholders(): int
    {
        $token = $this->resolveToken();
        $filters = $this->option('filters');
        $filtersArray = [];

        if ($filters) {
            $filtersArray = json_decode($filters, true, 512, JSON_THROW_ON_ERROR);
        }

        $response = $this->client->listCardholders($token, $filtersArray);
        $this->line(json_encode($response, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function handleCreateCardholder(): int
    {
        $token = $this->resolveToken();
        $payload = $this->option('payload');

        if (! $payload) {
            throw new RuntimeException('The --payload option is required for create-cardholder.');
        }

        $payloadData = json_decode(File::get($payload), true, 512, JSON_THROW_ON_ERROR);

        $response = $this->client->createCardholder($token, $payloadData);
        $this->line(json_encode($response, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function invalidAction(): int
    {
        $this->error('Unsupported Airwallex action.');

        return self::INVALID;
    }

    private function resolveToken(): string
    {
        if ($token = $this->option('token')) {
            return $token;
        }

        $response = $this->client->authenticate();

        if (! isset($response['token'])) {
            throw new RuntimeException('Failed to retrieve Airwallex token.');
        }

        return (string) $response['token'];
    }
}
