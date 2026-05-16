<?php

namespace Tests\Feature\Payables;

use App\Enums\PayableCategory;
use App\Enums\PayableStatus;
use App\Enums\PaymentMethod;
use App\Models\AccountPayable;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountPayableModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_detection_updates_open_payables(): void
    {
        $user = User::factory()->finance()->create();

        $payable = AccountPayable::factory()->create([
            'status' => PayableStatus::OPEN,
            'due_date' => now()->subDays(3)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('payables.index'))
            ->assertOk();

        $this->assertDatabaseHas('account_payables', [
            'id' => $payable->id,
            'status' => PayableStatus::OVERDUE->value,
        ]);
    }

    public function test_create_payable_with_installments_splits_values_and_due_dates(): void
    {
        $user = User::factory()->finance()->create();

        $this->actingAs($user)
            ->post(route('payables.store'), [
                'supplier' => 'Fornecedor Parcelado',
                'description' => 'Compra de insumos',
                'category' => PayableCategory::RAW_MATERIAL->value,
                'issue_date' => '2026-03-10',
                'due_date' => '2026-04-10',
                'amount' => 1000,
                'total_installments' => 3,
            ])
            ->assertRedirect();

        $installments = AccountPayable::query()
            ->where('supplier', 'Fornecedor Parcelado')
            ->orderBy('installment_number')
            ->get();

        $this->assertCount(3, $installments);
        $this->assertSame([1, 2, 3], $installments->pluck('installment_number')->all());
        $this->assertSame(['2026-04-10', '2026-05-10', '2026-06-10'], $installments->pluck('due_date')->map->toDateString()->all());
        $this->assertEquals(1000.00, (float) $installments->sum('amount'));
    }

    public function test_mark_as_paid_requires_required_fields(): void
    {
        $user = User::factory()->finance()->create();

        $payable = AccountPayable::factory()->open()->create([
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->from(route('payables.show', $payable))
            ->patch(route('payables.mark-paid', $payable), [])
            ->assertRedirect(route('payables.show', $payable))
            ->assertSessionHasErrors(['payment_date', 'paid_amount', 'payment_method']);
    }

    public function test_mark_as_paid_updates_work_order_actual_cost(): void
    {
        $user = User::factory()->finance()->create();

        $workOrder = WorkOrder::factory()->create([
            'contracted_amount' => 1000,
            'estimated_cost' => 400,
            'actual_cost' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $payable = AccountPayable::factory()->open()->create([
            'work_order_id' => $workOrder->id,
            'amount' => 280,
            'status' => PayableStatus::OPEN,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('payables.mark-paid', $payable), [
                'payment_date' => now()->toDateString(),
                'paid_amount' => 280,
                'payment_method' => PaymentMethod::PIX->value,
            ])
            ->assertRedirect(route('payables.show', $payable));

        $this->assertDatabaseHas('account_payables', [
            'id' => $payable->id,
            'status' => PayableStatus::PAID->value,
            'paid_amount' => 280,
        ]);

        $workOrder->refresh();
        $this->assertSame('280.00', (string) $workOrder->actual_cost);
    }

    public function test_cancelling_open_payable_keeps_actual_cost_based_on_paid_only(): void
    {
        $user = User::factory()->finance()->create();

        $workOrder = WorkOrder::factory()->create([
            'contracted_amount' => 1000,
            'estimated_cost' => 500,
            'actual_cost' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountPayable::factory()->paid()->create([
            'work_order_id' => $workOrder->id,
            'amount' => 350,
            'paid_amount' => 350,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $openPayable = AccountPayable::factory()->open()->create([
            'work_order_id' => $workOrder->id,
            'amount' => 250,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('payables.cancel', $openPayable), [
                'cancel_reason' => 'Compra nao realizada',
            ])
            ->assertRedirect(route('payables.show', $openPayable));

        $this->assertDatabaseHas('account_payables', [
            'id' => $openPayable->id,
            'status' => PayableStatus::CANCELLED->value,
        ]);

        $workOrder->refresh();
        $this->assertSame('350.00', (string) $workOrder->actual_cost);
    }

    public function test_filters_by_supplier_and_status(): void
    {
        $user = User::factory()->finance()->create();

        AccountPayable::factory()->create([
            'supplier' => 'Fornecedor Alfa',
            'status' => PayableStatus::OPEN,
            'due_date' => now()->addDays(2)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountPayable::factory()->create([
            'supplier' => 'Fornecedor Beta',
            'status' => PayableStatus::OVERDUE,
            'due_date' => now()->subDays(2)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('payables.index', [
                'supplier' => 'Alfa',
                'status' => PayableStatus::OPEN->value,
            ]))
            ->assertOk()
            ->assertViewHas('payables', function ($payables): bool {
                return $payables->count() === 1
                    && $payables->first()->supplier === 'Fornecedor Alfa'
                    && $payables->first()->status === PayableStatus::OPEN;
            });
    }
}
