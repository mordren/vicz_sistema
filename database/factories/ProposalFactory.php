<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    private static int $sequence = 1;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 8000, 280000);
        $probability = fake()->randomElement([20, 30, 50, 60, 75, 90]);
        $weightedAmount = round($amount * $probability / 100, 2);

        return [
            'number' => 'PROP-'.str_pad(self::$sequence++, 4, '0', STR_PAD_LEFT),
            'customer' => fake()->randomElement([
                'Fazenda São Lucas Ltda',
                'Agropecuária Cerrado Sul S.A.',
                'Cooperativa Central do Oeste',
                'Grãos do Triângulo',
                'Usina Boa Esperança',
                'Pecuária Pantanal S.A.',
                'Grupo Agromax',
                'Fazenda Nova Esperança',
                'Irrigação Norte Ltda',
            ]),
            'product_or_service_type' => fake()->randomElement([
                'Carreta Graneleira 30 Ton',
                'Plataforma de Corte 30 Pés',
                'Implemento Pulverizador 3000L',
                'Grade Aradora 20 Discos',
                'Subsolador 7 Hastes',
                'Reboque Agrícola Basculante',
                'Manutenção Preventiva Anual',
                'Reforma de Estrutura',
            ]),
            'description' => fake()->paragraph(),
            'amount' => $amount,
            'sent_at' => fake()->dateTimeBetween('-4 months', 'now'),
            'expected_close_date' => fake()->dateTimeBetween('now', '+3 months'),
            'close_probability' => $probability,
            'weighted_amount' => $weightedAmount,
            'status' => fake()->randomElement(ProposalStatus::cases()),
        ];
    }

    public function sent(): static
    {
        return $this->state(['status' => ProposalStatus::SENT]);
    }

    public function negotiating(): static
    {
        return $this->state([
            'status' => ProposalStatus::NEGOTIATING,
            'close_probability' => fake()->randomElement([50, 60, 75]),
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => ProposalStatus::APPROVED,
            'close_probability' => 100,
        ]);
    }

    public function lost(): static
    {
        return $this->state([
            'status' => ProposalStatus::LOST,
            'close_probability' => 0,
            'loss_reason' => fake()->randomElement([
                'Preço acima do mercado',
                'Cliente optou por concorrente',
                'Projeto foi cancelado',
                'Prazo de entrega incompatível',
                'Restrição orçamentária do cliente',
            ]),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => ProposalStatus::CANCELLED]);
    }
}
