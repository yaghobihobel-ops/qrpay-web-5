<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<int, class-string>
     */
    protected $commands = [
        \App\Console\Commands\DispatchAnalyticsEvent::class,
        \App\Console\Commands\RotateApiKeys::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('queue:retry all')->daily();
        $schedule->command('queue:work --stop-when-empty')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('currency:update')->daily();
        $schedule->command('audit:enforce-retention')->dailyAt('01:00');

        $schedule->command('keys:rotate')
            ->cron(config('key_management.rotation.cron', '0 */6 * * *'))
            ->withoutOverlapping()
            ->onFailure(fn () => \Log::warning('Key rotation command failed'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
