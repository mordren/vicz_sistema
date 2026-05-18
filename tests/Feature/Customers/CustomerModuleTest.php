<?php

namespace Tests\Feature\Customers;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CustomerModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_individual_customer_accepts_masked_values_and_confirmation(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->post(route('customers.store'), [
                'type' => CustomerType::INDIVIDUAL->value,
                'status' => CustomerStatus::ACTIVE->value,
                'name' => 'Joao Silva',
                'cpf' => '123.456.789-09',
                'phone' => '(11) 9999-8888',
                'whatsapp' => '(11) 98888-7777',
                'secondary_phone' => '(16) 3333-2222',
                'zip_code' => '12345-678',
                'city' => 'Ribeirao Preto',
                'state' => 'SP',
                'street' => 'Rua A',
                'number' => '100',
            ])
            ->assertRedirect();

        $customer = Customer::query()->firstOrFail();

        $this->assertSame(CustomerType::INDIVIDUAL, $customer->type);
        $this->assertSame('Joao Silva', $customer->name);
        $this->assertSame('12345678909', $customer->cpf);
        $this->assertSame('1199998888', $customer->phone);
        $this->assertSame('11988887777', $customer->whatsapp);
        $this->assertSame('1633332222', $customer->secondary_phone);
        $this->assertSame('12345678', $customer->zip_code);
    }

    public function test_store_company_customer_copies_legal_name_to_name_and_clears_personal_fields(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->post(route('customers.store'), [
                'type' => CustomerType::COMPANY->value,
                'status' => CustomerStatus::ACTIVE->value,
                'legal_name' => 'Fazenda Modelo LTDA',
                'cnpj' => '12.345.678/0001-95',
                'phone' => '(11) 4444-5555',
                'whatsapp' => '(11) 94444-5555',
                'secondary_phone' => '(16) 3333-4444',
                'zip_code' => '12345-678',
                'city' => 'Sao Paulo',
                'state' => 'SP',
                'street' => 'Avenida Central',
                'number' => '500',
            ])
            ->assertRedirect();

        $customer = Customer::query()->firstOrFail();

        $this->assertSame(CustomerType::COMPANY, $customer->type);
        $this->assertSame('Fazenda Modelo LTDA', $customer->name);
        $this->assertSame('Fazenda Modelo LTDA', $customer->legal_name);
        $this->assertSame('12345678000195', $customer->cnpj);
        $this->assertNull($customer->cpf);
    }

    public function test_dashboard_accepts_cached_string_category_rows_without_breaking(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 16, 10, 0, 0));

        try {
            $user = User::factory()->admin()->create();

            $startMonth = now()->copy()->startOfMonth()->subMonths(5);
            $months = [];
            $receivedByMonth = [];
            $expenseByMonth = [];

            for ($i = 0; $i < 6; $i++) {
                $date = $startMonth->copy()->addMonths($i);
                $key = $date->format('Y-m');
                $months[] = [
                    'key' => $key,
                    'label' => strtoupper($date->format('M')),
                ];
                $receivedByMonth[$key] = 0.0;
                $expenseByMonth[$key] = 0.0;
            }

            Cache::put(
                'dashboard_reports_v4_'.$user->id.'_finance',
                [
                    'months' => $months,
                    'receivedByMonth' => $receivedByMonth,
                    'expenseByMonth' => $expenseByMonth,
                    'maxChart' => 1,
                    'pendingReceivables' => 0,
                    'overdueReceivables' => 0,
                    'predictabilityScore' => 100,
                    'variation' => 0,
                    'expenseCategories' => ['Categorias legadas'],
                    'recentReceivables' => collect(),
                ],
                now()->addHour()
            );

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk();
        } finally {
            Carbon::setTestNow();
        }
    }
}
