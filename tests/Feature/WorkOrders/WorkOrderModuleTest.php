<?php

namespace Tests\Feature\WorkOrders;

use App\Enums\PayableStatus;
use App\Enums\ReceivableStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_summary_is_recalculated_from_linked_records(): void
    {
        $user = User::factory()->admin()->create();

        $workOrder = WorkOrder::factory()->create([
            'contracted_amount' => 1000,
            'estimated_cost' => 450,
            'actual_cost' => 0,
            'received_amount' => 0,
            'receivable_balance' => 1000,
            'estimated_margin' => 0,
            'actual_margin' => 0,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountPayable::factory()->paid()->create([
            'work_order_id' => $workOrder->id,
            'paid_amount' => 320,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountPayable::factory()->open()->create([
            'work_order_id' => $workOrder->id,
            'amount' => 900,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountReceivable::factory()->received()->create([
            'work_order_id' => $workOrder->id,
            'work_order_number' => $workOrder->number,
            'received_amount' => 700,
            'expected_amount' => 700,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        AccountReceivable::factory()->expected()->create([
            'work_order_id' => $workOrder->id,
            'work_order_number' => $workOrder->number,
            'expected_amount' => 200,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('work-orders.show', $workOrder))
            ->assertOk();

        $workOrder->refresh();

        $this->assertSame('320.00', (string) $workOrder->actual_cost);
        $this->assertSame('700.00', (string) $workOrder->received_amount);
        $this->assertSame('300.00', (string) $workOrder->receivable_balance);
        $this->assertSame('550.0000', (string) $workOrder->estimated_margin);
        $this->assertSame('680.0000', (string) $workOrder->actual_margin);
    }

    public function test_mark_as_delivered_requires_actual_delivery_date(): void
    {
        $operations = User::factory()->operations()->create();

        $workOrder = WorkOrder::factory()->create([
            'responsible_user_id' => $operations->id,
            'status' => WorkOrderStatus::READY_FOR_DELIVERY,
            'progress_percentage' => 95,
            'created_by' => $operations->id,
            'updated_by' => $operations->id,
        ]);

        $this->actingAs($operations)
            ->from(route('work-orders.show', $workOrder))
            ->patch(route('work-orders.deliver', $workOrder), [])
            ->assertRedirect(route('work-orders.show', $workOrder))
            ->assertSessionHasErrors(['actual_delivery_date']);
    }

    public function test_operational_update_is_restricted_to_assigned_operations_user(): void
    {
        $assigned = User::factory()->operations()->create();
        $otherOperations = User::factory()->operations()->create();

        $workOrder = WorkOrder::factory()->create([
            'responsible_user_id' => $assigned->id,
            'status' => WorkOrderStatus::IN_PRODUCTION,
            'progress_percentage' => 25,
            'created_by' => $assigned->id,
            'updated_by' => $assigned->id,
        ]);

        $this->actingAs($otherOperations)
            ->patch(route('work-orders.operational-update', $workOrder), [
                'status' => WorkOrderStatus::PAINTING->value,
                'progress_percentage' => 60,
            ])
            ->assertForbidden();

        $this->actingAs($assigned)
            ->patch(route('work-orders.operational-update', $workOrder), [
                'status' => WorkOrderStatus::PAINTING->value,
                'progress_percentage' => 60,
            ])
            ->assertRedirect(route('work-orders.show', $workOrder));

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'status' => WorkOrderStatus::PAINTING->value,
            'progress_percentage' => 60,
        ]);
    }

    public function test_index_highlights_overdue_and_near_delivery_work_orders(): void
    {
        $user = User::factory()->admin()->create();

        WorkOrder::factory()->create([
            'customer' => 'Cliente Atrasado',
            'status' => WorkOrderStatus::IN_PRODUCTION,
            'expected_delivery_date' => now()->subDay()->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        WorkOrder::factory()->create([
            'customer' => 'Cliente Proximo',
            'status' => WorkOrderStatus::IN_PRODUCTION,
            'expected_delivery_date' => now()->addDays(2)->toDateString(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('work-orders.index'))
            ->assertOk()
            ->assertSee('Cliente Atrasado')
            ->assertSee('Em atraso')
            ->assertSee('Cliente Proximo')
            ->assertSee('Próxima da entrega');
    }

    public function test_finance_role_cannot_access_operational_update_actions(): void
    {
        $finance = User::factory()->finance()->create();
        $operations = User::factory()->operations()->create();

        $workOrder = WorkOrder::factory()->create([
            'responsible_user_id' => $operations->id,
            'status' => WorkOrderStatus::IN_PRODUCTION,
            'created_by' => $operations->id,
            'updated_by' => $operations->id,
        ]);

        $this->actingAs($finance)
            ->patch(route('work-orders.operational-update', $workOrder), [
                'status' => WorkOrderStatus::ASSEMBLY->value,
                'progress_percentage' => 70,
            ])
            ->assertForbidden();
    }
}
