<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Enums\ReceivableType;
use App\Models\AccountReceivable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountReceivable>
 */
class AccountReceivableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer' => fake()->randomElement([
                'Fazenda São Lucas Ltda',
                'Agropecuária Cerrado Sul S.A.',
                'Cooperativa Central do Oeste',
                'Grãos do Triângulo',
                'Usina Boa Esperança',
                'Pecuária Pantanal S.A.',
                'Grupo Agromax',
                'Fazenda Santa Rita',
                'Irrigação Norte Ltda',
                'JBS Implementos Agro',
            ]),
            'description' => fake()->sentence(4),
            'receivable_type' => fake()->randomElement(ReceivableType::cases()),
            'expected_date' => fake()->dateTimeBetween('-2 months', '+3 months'),
            'expected_amount' => fake()->randomFloat(2, 1000, 120000),
            'status' => ReceivableStatus::EXPECTED,
        ];
    }

    public function expected(): static
    {
        return $this->state([
            'status' => ReceivableStatus::EXPECTED,
            'expected_date' => fake()->dateTimeBetween('now', '+2 months'),
        ]);
    }

    public function received(): static
    {
        $date = fake()->dateTimeBetween('-3 months', 'now');
        return $this->state(fn (array $attr) => [
            'status' => ReceivableStatus::RECEIVED,
            'received_date' => $date,
            'received_amount' => $attr['expected_amount'],
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'status' => ReceivableStatus::OVERDUE,
            'expected_date' => fake()->dateTimeBetween('-60 days', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => ReceivableStatus::CANCELLED]);
    }

    public function dueToday(): static
    {
        return $this->state(['expected_date' => now()->toDateString(), 'status' => ReceivableStatus::EXPECTED]);
    }
}
