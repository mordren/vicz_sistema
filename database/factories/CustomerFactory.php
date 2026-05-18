<?php

namespace Database\Factories;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(CustomerType::cases());

        if ($type === CustomerType::INDIVIDUAL) {
            return [
                'type' => $type,
                'name' => fake()->name(),
                'cpf' => fake()->unique()->numerify('###########'),
                'email' => fake()->safeEmail(),
                'phone' => fake()->numerify('###########'),
                'whatsapp' => fake()->numerify('###########'),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'country' => 'Brasil',
                'status' => CustomerStatus::ACTIVE,
                'credit_limit' => fake()->randomFloat(2, 0, 200000),
            ];
        }

        return [
            'type' => CustomerType::COMPANY,
            'name' => fake()->company(),
            'legal_name' => fake()->company().' LTDA',
            'cnpj' => fake()->unique()->numerify('##############'),
            'state_registration' => fake()->numerify('##########'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->numerify('###########'),
            'whatsapp' => fake()->numerify('###########'),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'country' => 'Brasil',
            'status' => CustomerStatus::ACTIVE,
            'credit_limit' => fake()->randomFloat(2, 10000, 600000),
        ];
    }

    public function individual(): static
    {
        return $this->state([
            'type' => CustomerType::INDIVIDUAL,
            'cnpj' => null,
            'legal_name' => null,
            'state_registration' => null,
            'municipal_registration' => null,
        ]);
    }

    public function company(): static
    {
        return $this->state([
            'type' => CustomerType::COMPANY,
            'cpf' => null,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(['status' => CustomerStatus::BLOCKED]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => CustomerStatus::INACTIVE]);
    }
}
