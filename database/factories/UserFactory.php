<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(UserRole::cases()),
            'active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::ADMIN]);
    }

    public function finance(): static
    {
        return $this->state(['role' => UserRole::FINANCE]);
    }

    public function operations(): static
    {
        return $this->state(['role' => UserRole::OPERATIONS]);
    }

    public function sales(): static
    {
        return $this->state(['role' => UserRole::SALES]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
