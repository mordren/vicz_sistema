<?php

namespace Tests\Feature\Listings;

use App\Enums\PayableStatus;
use App\Enums\ReceivableStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Alert;
use App\Models\Customer;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_payables_can_be_sorted_by_amount_in_both_directions(): void
    {
        $admin = User::factory()->admin()->create();
        AccountPayable::factory()->open()->create(['supplier' => 'Medio', 'amount' => 500]);
        AccountPayable::factory()->open()->create(['supplier' => 'Barato', 'amount' => 100]);
        AccountPayable::factory()->open()->create(['supplier' => 'Caro', 'amount' => 900]);

        $this->actingAs($admin)
            ->get(route('payables.index', ['sort' => 'amount', 'direction' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['Barato', 'Medio', 'Caro']);

        $this->actingAs($admin)
            ->get(route('payables.index', ['sort' => 'amount', 'direction' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['Caro', 'Medio', 'Barato']);
    }

    public function test_paid_payables_default_to_most_recent_payment_date_first(): void
    {
        $admin = User::factory()->admin()->create();
        AccountPayable::factory()->paid()->create(['supplier' => 'Pago Antigo', 'payment_date' => now()->subDays(30), 'due_date' => now()->addDays(5)]);
        AccountPayable::factory()->paid()->create(['supplier' => 'Pago Recente', 'payment_date' => now()->subDay(), 'due_date' => now()->subDays(60)]);
        AccountPayable::factory()->paid()->create(['supplier' => 'Pago Meio', 'payment_date' => now()->subDays(10), 'due_date' => now()->subDays(1)]);

        $this->actingAs($admin)
            ->get(route('payables.index', ['status' => PayableStatus::PAID->value]))
            ->assertOk()
            ->assertSeeInOrder(['Pago Recente', 'Pago Meio', 'Pago Antigo']);
    }

    public function test_received_receivables_default_to_most_recent_received_date_first(): void
    {
        $admin = User::factory()->admin()->create();
        AccountReceivable::factory()->received()->create(['customer' => 'Recebido Antigo', 'received_date' => now()->subDays(30)]);
        AccountReceivable::factory()->received()->create(['customer' => 'Recebido Recente', 'received_date' => now()->subDay()]);

        $this->actingAs($admin)
            ->get(route('receivables.index', ['status' => ReceivableStatus::RECEIVED->value]))
            ->assertOk()
            ->assertSeeInOrder(['Recebido Recente', 'Recebido Antigo']);
    }

    public function test_rows_without_a_value_stay_last_in_both_directions(): void
    {
        $admin = User::factory()->admin()->create();
        AccountPayable::factory()->open()->create(['supplier' => 'Sem Pagamento', 'payment_date' => null]);
        AccountPayable::factory()->paid()->create(['supplier' => 'Pago Janeiro', 'payment_date' => '2026-01-10']);
        AccountPayable::factory()->paid()->create(['supplier' => 'Pago Marco', 'payment_date' => '2026-03-10']);

        $this->actingAs($admin)
            ->get(route('payables.index', ['sort' => 'payment_date', 'direction' => 'asc']))
            ->assertSeeInOrder(['Pago Janeiro', 'Pago Marco', 'Sem Pagamento']);

        $this->actingAs($admin)
            ->get(route('payables.index', ['sort' => 'payment_date', 'direction' => 'desc']))
            ->assertSeeInOrder(['Pago Marco', 'Pago Janeiro', 'Sem Pagamento']);
    }

    public function test_unknown_sort_column_is_ignored(): void
    {
        $admin = User::factory()->admin()->create();
        AccountPayable::factory()->open()->create();

        $this->actingAs($admin)
            ->get(route('payables.index', ['sort' => 'id; drop table users', 'direction' => 'sideways']))
            ->assertOk();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_every_listing_accepts_every_sort_column(): void
    {
        $admin = User::factory()->admin()->create();
        AccountPayable::factory()->count(2)->create();
        AccountReceivable::factory()->count(2)->create();
        Proposal::factory()->create(['number' => 'PROP-T1', 'sales_owner_id' => $admin->id]);
        Proposal::factory()->create(['number' => 'PROP-T2']);
        WorkOrder::factory()->create(['responsible_user_id' => $admin->id]);
        WorkOrder::factory()->create();
        Customer::factory()->count(2)->create();
        Alert::factory()->count(2)->create();

        $listings = [
            'payables.index' => ['supplier', 'description', 'category', 'installment', 'due_date', 'payment_date', 'amount', 'status'],
            'receivables.index' => ['customer', 'description', 'type', 'expected_date', 'received_date', 'amount', 'status', 'payment_method'],
            'proposals.index' => ['number', 'customer', 'sales_owner', 'expected_close_date', 'close_probability', 'amount', 'weighted_amount', 'status'],
            'work-orders.index' => ['number', 'customer', 'service_type', 'responsible', 'progress', 'expected_delivery_date', 'receivable_balance', 'status'],
            'customers.index' => ['name', 'type', 'status', 'work_orders'],
            'alerts.index' => ['severity', 'title', 'type', 'status', 'created_at'],
            'settings.users.index' => ['name', 'email', 'role', 'active', 'created_at'],
        ];

        foreach ($listings as $route => $columns) {
            foreach ($columns as $column) {
                foreach (['asc', 'desc'] as $direction) {
                    $this->actingAs($admin)
                        ->get(route($route, ['sort' => $column, 'direction' => $direction]))
                        ->assertOk()
                        ->assertSee('aria-sort="'.($direction === 'asc' ? 'ascending' : 'descending').'"', false);
                }
            }
        }
    }
}
