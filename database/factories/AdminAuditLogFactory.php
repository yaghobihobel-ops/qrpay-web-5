<?php

namespace Database\Factories;

use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AdminAuditLog>
 */
class AdminAuditLogFactory extends Factory
{
    protected $model = AdminAuditLog::class;

    public function definition(): array
    {
        return [
            'admin_id' => $this->faker->numberBetween(1, 50),
            'action' => $this->faker->randomElement(['GET admin.dashboard', 'POST admin.users.store']),
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'payload' => ['sample' => $this->faker->word()],
            'status_code' => $this->faker->randomElement([200, 201, 204, 400, 401, 500]),
            'retention_expires_at' => now()->addDays($this->faker->numberBetween(1, 365)),
        ];
    }

    public function expired(): self
    {
        return $this->state(function () {
            return [
                'retention_expires_at' => now()->subDay(),
            ];
        });
    }
}
