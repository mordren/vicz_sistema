<?php

namespace Tests\Feature\Receivables;

use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Enums\ReceivableType;
use App\Models\AccountReceivable;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountReceivableModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_detection_updates_expected_receivables(): void
    {
        $user = User::factory()->finance()->create();

        $receivable = AccountReceivable::factory()->create([
            'status' => ReceivableStatus::EXPECTED,
            'expected_date' => now()->subDays(3)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('receivables.index'))
            ->assertOk();

        $this->assertDatabaseHas('account_receivables', [
            'id' => $receivable->id,
            'status' => ReceivableStatus::OVERDUE->value,
        ]);
    }

    public function test_mark_as_received_requires_required_fields(): void
    {
        $user = User::factory()->finance()->create();

        $receivable = AccountReceivable::factory()->expected()->create([
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('receivables.show', $receivable))
            ->patch(route('receivables.mark-received', $receivable), [])
            ->assertRedirect(route('receivables.show', $receivable))
            ->assertSessionHasErrors(['received_date', 'received_amount', 'payment_method']);
    }

    public function test_mark_as_received_updates_work_order_amounts_and_balance(): void
    {
        $user = User::factory()->finance()->create();

        $workOrder = WorkOrder::factory()->create([
            'contracted_amount' => 1000,
            'received_amount' => 0,
            'receivable_balance' => 1000,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $receivable = AccountReceivable::factory()->expected()->create([
            'work_order_id' => $workOrder->id,
            'work_order_number' => $workOrder->number,
            'expected_amount' => 300,
            'status' => ReceivableStatus::EXPECTED,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('receivables.mark-received', $receivable), [
                'received_date' => now()->toDateString(),
                'received_amount' => 300,
                'payment_method' => PaymentMethod::PIX->value,
            ])
            ->assertRedirect(route('receivables.show', $receivable));

        $this->assertDatabaseHas('account_receivables', [
            'id' => $receivable->id,
            'status' => ReceivableStatus::RECEIVED->value,
            'received_amount' => 300,
        ]);

        $workOrder->refresh();
        $this->assertSame('300.00', (string) $workOrder->received_amount);
        $this->assertSame('700.00', (string) $workOrder->receivable_balance);
    }

    public function test_cancel_receivable_sets_status_and_keeps_work_order_based_on_received_only(): void
    {
        $user = User::factory()->finance()->create();

        $workOrder = WorkOrder::factory()->create([
            'contracted_amount' => 1000,
            'received_amount' => 0,
            'receivable_balance' => 1000,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountReceivable::factory()->received()->create([
            'work_order_id' => $workOrder->id,
            'work_order_number' => $workOrder->number,
            'expected_amount' => 400,
            'received_amount' => 400,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $pending = AccountReceivable::factory()->expected()->create([
            'work_order_id' => $workOrder->id,
            'work_order_number' => $workOrder->number,
            'expected_amount' => 200,
            'status' => ReceivableStatus::EXPECTED,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('receivables.cancel', $pending), [
                'cancel_reason' => 'Cliente desistiu do serviço',
            ])
            ->assertRedirect(route('receivables.show', $pending));

        $this->assertDatabaseHas('account_receivables', [
            'id' => $pending->id,
            'status' => ReceivableStatus::CANCELLED->value,
        ]);

        $workOrder->refresh();
        $this->assertSame('400.00', (string) $workOrder->received_amount);
        $this->assertSame('600.00', (string) $workOrder->receivable_balance);
    }

    public function test_filters_by_customer_and_status(): void
    {
        $user = User::factory()->finance()->create();

        AccountReceivable::factory()->create([
            'customer' => 'Cliente Alfa',
            'status' => ReceivableStatus::EXPECTED,
            'expected_date' => now()->addDays(3)->toDateString(),
            'receivable_type' => ReceivableType::INSTALLMENT,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountReceivable::factory()->create([
            'customer' => 'Cliente Beta',
            'status' => ReceivableStatus::OVERDUE,
            'expected_date' => now()->subDays(2)->toDateString(),
            'receivable_type' => ReceivableType::DOWN_PAYMENT,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('receivables.index', [
                'customer' => 'Alfa',
                'status' => ReceivableStatus::EXPECTED->value,
            ]))
            ->assertOk()
            ->assertViewHas('receivables', function ($receivables): bool {
                return $receivables->count() === 1
                    && $receivables->first()->customer === 'Cliente Alfa'
                    && $receivables->first()->status === ReceivableStatus::EXPECTED;
            });
    }
}
