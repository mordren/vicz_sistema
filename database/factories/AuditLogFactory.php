<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted', 'paid', 'delivered']),
            'auditable_type' => 'App\\Models\\WorkOrder',
            'auditable_id' => 1,
            'before' => null,
            'after' => ['status' => 'IN_PRODUCTION'],
            'created_at' => now(),
        ];
    }
}
