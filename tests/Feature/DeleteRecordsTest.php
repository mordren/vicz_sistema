<?php

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\TestCase;

class DeleteRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_received_receivable_updates_the_work_order_balance(): void
    {
        $admin = User::factory()->admin()->create();
        $workOrder = WorkOrder::factory()->create(['contracted_amount' => 1000, 'received_amount' => 0, 'receivable_balance' => 1000]);

        $receivable = AccountReceivable::factory()->expected()->create([
            'work_order_id' => $workOrder->id,
            'expected_amount' => 400,
        ]);

        $this->actingAs($admin)->patch(route('receivables.mark-received', $receivable), [
            'received_date' => now()->toDateString(),
            'received_amount' => 400,
            'payment_method' => 'PIX',
        ])->assertSessionHasNoErrors();

        $this->assertSame(600.0, (float) $workOrder->fresh()->receivable_balance);

        $this->actingAs($admin)
            ->delete(route('receivables.destroy', $receivable))
            ->assertRedirect(route('receivables.index'));

        $this->assertModelMissing($receivable);
        $workOrder->refresh();
        $this->assertSame(0.0, (float) $workOrder->received_amount);
        $this->assertSame(1000.0, (float) $workOrder->receivable_balance);
    }

    public function test_deleting_a_payable_updates_the_work_order_actual_cost(): void
    {
        $admin = User::factory()->admin()->create();
        $workOrder = WorkOrder::factory()->create(['contracted_amount' => 1000, 'actual_cost' => 0]);

        $keep = AccountPayable::factory()->open()->create(['work_order_id' => $workOrder->id, 'amount' => 100]);
        $remove = AccountPayable::factory()->open()->create(['work_order_id' => $workOrder->id, 'amount' => 300]);

        $this->actingAs($admin)
            ->delete(route('payables.destroy', $remove))
            ->assertRedirect(route('payables.index'));

        $this->assertModelMissing($remove);
        $this->assertModelExists($keep);
        $this->assertSame(100.0, (float) $workOrder->fresh()->actual_cost);
    }

    public function test_only_admins_can_delete_financial_records(): void
    {
        $finance = User::factory()->finance()->create();
        $payable = AccountPayable::factory()->open()->create();
        $receivable = AccountReceivable::factory()->expected()->create();

        $this->actingAs($finance)->delete(route('payables.destroy', $payable))->assertForbidden();
        $this->actingAs($finance)->delete(route('receivables.destroy', $receivable))->assertForbidden();

        $this->assertModelExists($payable);
        $this->assertModelExists($receivable);
    }

    public function test_deleting_a_converted_proposal_keeps_the_work_order(): void
    {
        $admin = User::factory()->admin()->create();
        $proposal = Proposal::factory()->create(['number' => 'PROP-DEL']);
        $workOrder = WorkOrder::factory()->create(['proposal_id' => $proposal->id]);

        $this->actingAs($admin)->delete(route('proposals.destroy', $proposal))->assertRedirect();

        $this->assertModelMissing($proposal);
        $this->assertNull($workOrder->fresh()->proposal_id);
    }

    public function test_deleting_a_user_keeps_their_records(): void
    {
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->operations()->create();
        $workOrder = WorkOrder::factory()->create(['responsible_user_id' => $operator->id, 'created_by' => $operator->id]);

        $this->actingAs($admin)
            ->delete(route('settings.users.destroy', $operator))
            ->assertRedirect(route('settings.users.index'));

        $this->assertModelMissing($operator);
        $this->assertModelExists($workOrder);
        $this->assertNull($workOrder->fresh()->responsible_user_id);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('settings.users.destroy', $admin))
            ->assertRedirect(route('settings.users.index'));

        $this->assertModelExists($admin);
    }

    public function test_user_names_are_escaped_in_the_delete_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => "João D'Ávila"]);

        $this->actingAs($admin)
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertDontSee("confirm('Excluir o usuário João D&#039;Ávila", false)
            ->assertSee('return confirm('.Js::from("Excluir o usuário João D'Ávila permanentemente?").')', false);
    }
}
