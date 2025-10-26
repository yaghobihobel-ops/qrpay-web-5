<?php

namespace App\Console\Commands;

use App\Jobs\SyncExchangeRates;
use App\Services\Exchange\ExchangeRateManager;
use App\Services\Exchange\Exceptions\ExchangeRateException;
use Illuminate\Console\Command;

class UpdateExchangeRates extends Command
{
    protected $signature = 'exchange:update {--symbols=} {--sync : Run the update synchronously without queueing the job}';

    protected $description = 'Update exchange rates using the configured official providers.';

    public function __construct(protected ExchangeRateManager $manager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbols = $this->resolveSymbols();

        if (empty($symbols)) {
            $this->error('No currency symbols provided for update.');

            return self::FAILURE;
        }

        try {
            $rates = $this->manager->fetchRates($symbols);
        } catch (ExchangeRateException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (empty($rates)) {
            $this->warn('Exchange providers returned an empty response.');

            return self::SUCCESS;
        }

        $baseCurrency = config('exchange.default_base_currency');

        if ($this->option('sync')) {
            SyncExchangeRates::dispatchSync($rates, $baseCurrency);
        } else {
            SyncExchangeRates::dispatch($rates, $baseCurrency);
        }

        $this->info('Exchange rates queued for update: ' . implode(', ', array_keys($rates)));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function resolveSymbols(): array
    {
        $symbolsOption = $this->option('symbols');
        $symbols = [];

        if (is_string($symbolsOption) && strlen($symbolsOption) > 0) {
            $symbols = array_map('trim', explode(',', $symbolsOption));
        }

        if (empty($symbols)) {
            $symbols = config('exchange.default_symbols', []);
        }

        return array_values(array_filter(array_map('strtoupper', $symbols)));
    }
}
