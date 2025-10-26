<?php

namespace Tests\Feature\Console;

use App\Models\AdminAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Tests\TestCase;

class EnforceAuditLogRetentionCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_deletes_expired_logs_and_reports_the_total()
    {
        AdminAuditLog::factory()->count(2)->expired()->create();
        AdminAuditLog::factory()->create([
            'retention_expires_at' => Carbon::now()->addDay(),
        ]);

        $this->artisan('audit:enforce-retention')
            ->expectsOutput('Removed 2 expired audit logs.')
            ->assertExitCode(SymfonyCommand::SUCCESS);

        $this->assertSame(1, AdminAuditLog::count());
    }

    /** @test */
    public function it_reports_deleted_ids_when_requested()
    {
        $ids = AdminAuditLog::factory()->count(3)->expired()->create()->pluck('id');

        $this->artisan('audit:enforce-retention --report')
            ->expectsOutput('Purging audit log IDs: ' . $ids->implode(', '))
            ->expectsOutput('Removed 3 expired audit logs.')
            ->assertExitCode(SymfonyCommand::SUCCESS);

        $this->assertSame(0, AdminAuditLog::count());
    }
}
