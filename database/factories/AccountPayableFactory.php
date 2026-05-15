<?php

namespace Database\Factories;

use App\Enums\PayableCategory;
use App\Enums\PayableStatus;
use App\Enums\PaymentMethod;
use App\Models\AccountPayable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountPayable>
 */
class AccountPayableFactory extends Factory
{
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-6 months', 'now');
        $dueDate = fake()->dateTimeBetween($issueDate, '+3 months');

        return [
            'supplier' => fake()->randomElement([
                'Aço Minas Distribuidora Ltda',
                'Indústria Metálica Paraná S.A.',
                'Tubos & Perfis do Sul',
                'Tintas Industriais Horizonte',
                'Hidráulica Agrícola Center',
                'Pneumáticos do Cerrado',
                'Elétrica e Automação Oeste',
                'Metalúrgica Rio Grande',
                'Folha Color Pinturas Ind.',
                'Peças e Componentes Agro',
            ]),
            'description' => fake()->sentence(4),
            'category' => fake()->randomElement(PayableCategory::cases()),
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'amount' => fake()->randomFloat(2, 200, 85000),
            'installment_number' => 1,
            'total_installments' => 1,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => PayableStatus::OPEN,
            'cost_center' => fake()->randomElement(['PRODUÇÃO', 'ADMINISTRATIVO', 'COMERCIAL', 'MANUTENÇÃO']),
            'notes' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => PayableStatus::OPEN]);
    }

    public function paid(): static
    {
        $paymentDate = fake()->dateTimeBetween('-3 months', 'now');
        return $this->state(fn (array $attr) => [
            'status' => PayableStatus::PAID,
            'payment_date' => $paymentDate,
            'paid_amount' => $attr['amount'],
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => PayableStatus::OVERDUE,
            'due_date' => fake()->dateTimeBetween('-60 days', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => PayableStatus::CANCELLED]);
    }

    public function dueToday(): static
    {
        return $this->state(['due_date' => now()->toDateString(), 'status' => PayableStatus::OPEN]);
    }

    public function dueTomorrow(): static
    {
        return $this->state(['due_date' => now()->addDay()->toDateString(), 'status' => PayableStatus::OPEN]);
    }
}
