<?php

namespace Database\Factories;

use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    private static int $sequence = 1;

    public function definition(): array
    {
        $contracted = fake()->randomFloat(2, 15000, 350000);
        $downPayment = round($contracted * fake()->randomFloat(2, 0.2, 0.4), 2);
        $estimatedCost = round($contracted * fake()->randomFloat(2, 0.5, 0.75), 2);
        $estimatedMargin = round(($contracted - $estimatedCost) / $contracted, 4);
        $openedAt = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'number' => 'OS-'.str_pad(self::$sequence++, 5, '0', STR_PAD_LEFT),
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
            'service_type' => fake()->randomElement([
                'Carreta Graneleira',
                'Plataforma de Corte',
                'Implemento Pulverizador',
                'Grade Aradora',
                'Subsolador 5 Hastes',
                'Reboque Agrícola',
                'Plantadeira 11 Linhas',
                'Manutenção Geral',
                'Reforma Estrutural',
            ]),
            'description' => fake()->paragraph(),
            'opened_at' => $openedAt,
            'expected_delivery_date' => fake()->dateTimeBetween($openedAt, '+4 months'),
            'status' => fake()->randomElement(WorkOrderStatus::cases()),
            'progress_percentage' => fake()->numberBetween(0, 100),
            'contracted_amount' => $contracted,
            'down_payment_amount' => $downPayment,
            'received_amount' => $downPayment,
            'receivable_balance' => round($contracted - $downPayment, 2),
            'estimated_cost' => $estimatedCost,
            'actual_cost' => 0,
            'estimated_margin' => $estimatedMargin,
            'actual_margin' => 0,
        ];
    }

    public function inProduction(): static
    {
        return $this->state([
            'status' => WorkOrderStatus::IN_PRODUCTION,
            'progress_percentage' => fake()->numberBetween(10, 70),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attr) => [
            'status' => WorkOrderStatus::DELIVERED,
            'progress_percentage' => 100,
            'actual_delivery_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'received_amount' => $attr['contracted_amount'],
            'receivable_balance' => 0,
            'actual_cost' => $attr['estimated_cost'] * fake()->randomFloat(2, 0.9, 1.1),
        ]);
    }

    public function overdueDelivery(): static
    {
        return $this->state([
            'status' => WorkOrderStatus::IN_PRODUCTION,
            'expected_delivery_date' => fake()->dateTimeBetween('-30 days', '-1 day'),
            'progress_percentage' => fake()->numberBetween(40, 85),
        ]);
    }

    public function readyForDelivery(): static
    {
        return $this->state([
            'status' => WorkOrderStatus::READY_FOR_DELIVERY,
            'progress_percentage' => 100,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => WorkOrderStatus::CANCELLED,
            'progress_percentage' => 0,
        ]);
    }
}
