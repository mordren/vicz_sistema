<?php

namespace Database\Factories;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Models\Alert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alert>
 */
class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(AlertType::cases())->value,
            'severity' => $this->faker->randomElement(AlertSeverity::cases()),
            'title' => $this->faker->sentence(6),
            'message' => $this->faker->sentence(12),
            'entity_type' => null,
            'entity_id' => null,
            'status' => AlertStatus::OPEN,
            'resolved_at' => null,
        ];
    }

    public function critical(): static
    {
        return $this->state(['severity' => AlertSeverity::CRITICAL]);
    }

    public function warning(): static
    {
        return $this->state(['severity' => AlertSeverity::WARNING]);
    }

    public function resolved(): static
    {
        return $this->state([
            'status' => AlertStatus::RESOLVED,
            'resolved_at' => now(),
        ]);
    }
}
