<?php

namespace App\Console\Commands;

use App\Services\Fakes\FakeScenarioRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SeedSandboxScenarios extends Command
{
    protected $signature = 'sandbox:seed {--append : Merge generated data with existing sandbox scenarios}';

    protected $description = 'Generate deterministic fake provider scenarios for the sandbox environment.';

    public function handle(): int
    {
        $repository = new FakeScenarioRepository();
        $existing = $repository->load();
        $seed = $repository->defaultData();

        $dynamicPayment = [
            'reference' => 'PAY-' . Str::upper(Str::random(8)),
            'status' => 'success',
            'amount' => Arr::random([55.25, 73.10, 89.99]),
            'currency' => 'USD',
            'channel' => Arr::random(['card', 'bank', 'wallet']),
        ];
        $seed['payments'][] = $dynamicPayment;

        $seed['exchange_rates']['USDNGN'] = 1500.50;

        if ($this->option('append')) {
            $merged = array_replace_recursive($seed, $existing);
        } else {
            $merged = $seed;
        }

        $repository->store($merged);

        $this->info('Sandbox fake provider scenarios generated successfully.');
        $this->line('Payment references:');
        foreach ($merged['payments'] as $payment) {
            $this->line('- ' . $payment['reference'] . ' [' . $payment['status'] . ']');
        }

        return self::SUCCESS;
    }
}
