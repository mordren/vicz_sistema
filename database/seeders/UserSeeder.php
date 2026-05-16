<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Carlos Eduardo — Admin',
            'email' => 'admin@vicz.com.br',
            'password' => Hash::make('password'),
        ]);

        User::factory()->finance()->create([
            'name' => 'Ana Lima — Financeiro',
            'email' => 'financeiro@vicz.com.br',
            'password' => Hash::make('password'),
        ]);

        User::factory()->operations()->create([
            'name' => 'Roberto Silva — Operações',
            'email' => 'operacoes@vicz.com.br',
            'password' => Hash::make('password'),
        ]);

        User::factory()->sales()->create([
            'name' => 'Fernanda Souza — Comercial',
            'email' => 'comercial@vicz.com.br',
            'password' => Hash::make('password'),
        ]);
    }
}
