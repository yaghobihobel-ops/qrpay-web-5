<?php

namespace App\Console\Commands;

use App\Support\Reconciliation\SettlementReportGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ExportSettlementReport extends Command
{
    protected $signature = 'reconciliation:export {--format=json : Output format (json|csv)} {--start=} {--end=}';

    protected $description = 'Export reconciliation settlement aggregates for the requested window.';

    public function handle(SettlementReportGenerator $generator): int
    {
        $format = strtolower($this->option('format')) ?: 'json';
        $start = $this->option('start');
        $end = $this->option('end');

        $startAt = $start ? CarbonImmutable::parse($start) : null;
        $endAt = $end ? CarbonImmutable::parse($end) : null;

        $report = $generator->generate($startAt, $endAt);

        if ($format === 'csv') {
            $this->outputCsv($report->all());
        } else {
            $this->line($report->toJson(JSON_PRETTY_PRINT));
        }

        return self::SUCCESS;
    }

    protected function outputCsv(array $rows): void
    {
        if (empty($rows)) {
            $this->line('country_code,channel,provider_key,status,total_events,valid_signatures,first_seen,last_seen');
            return;
        }

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        while (($line = fgets($handle)) !== false) {
            $this->line(trim($line));
        }
        fclose($handle);
    }
}
