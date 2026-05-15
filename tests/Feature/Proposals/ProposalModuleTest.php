<?php

namespace Tests\Feature\Proposals;

use App\Enums\ProposalStatus;
use App\Models\AccountReceivable;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_weighted_amount_is_calculated_on_create(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)
            ->post(route('proposals.store'), [
                'customer' => 'Cliente Norte',
                'product_or_service_type' => 'Carreta graneleira',
                'description' => 'Proposta teste',
                'amount' => 1000,
                'close_probability' => 25,
                'sales_owner_id' => $sales->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('proposals', [
            'customer' => 'Cliente Norte',
            'weighted_amount' => 250,
        ]);
    }

    public function test_lost_proposal_requires_loss_reason(): void
    {
        $sales = User::factory()->sales()->create();
        $proposal = Proposal::factory()->sent()->create([
            'sales_owner_id' => $sales->id,
            'created_by' => $sales->id,
            'updated_by' => $sales->id,
        ]);

        $this->actingAs($sales)
            ->from(route('proposals.show', $proposal))
            ->patch(route('proposals.mark-lost', $proposal), [])
            ->assertRedirect(route('proposals.show', $proposal))
            ->assertSessionHasErrors(['loss_reason']);
    }

    public function test_approve_proposal_sets_status_and_probability_to_100(): void
    {
        $sales = User::factory()->sales()->create();
        $proposal = Proposal::factory()->negotiating()->create([
            'amount' => 1200,
            'close_probability' => 60,
            'weighted_amount' => 720,
            'sales_owner_id' => $sales->id,
            'created_by' => $sales->id,
            'updated_by' => $sales->id,
        ]);

        $this->actingAs($sales)
            ->patch(route('proposals.approve', $proposal), [])
            ->assertRedirect(route('proposals.show', $proposal));

        $proposal->refresh();

        $this->assertSame(ProposalStatus::APPROVED, $proposal->status);
        $this->assertSame(100, $proposal->close_probability);
        $this->assertSame('1200.00', (string) $proposal->weighted_amount);
    }

    public function test_cancelled_and_lost_do_not_affect_weighted_forecast_summary(): void
    {
        $admin = User::factory()->admin()->create();

        Proposal::factory()->create([
            'status' => ProposalStatus::SENT,
            'amount' => 1000,
            'close_probability' => 50,
            'weighted_amount' => 500,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Proposal::factory()->create([
            'status' => ProposalStatus::APPROVED,
            'amount' => 900,
            'close_probability' => 100,
            'weighted_amount' => 900,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Proposal::factory()->lost()->create([
            'amount' => 800,
            'weighted_amount' => 0,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Proposal::factory()->cancelled()->create([
            'amount' => 700,
            'weighted_amount' => 0,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('proposals.index'))
            ->assertOk()
            ->assertSee('R$ 1.400,00');
    }

    public function test_approved_proposal_converts_to_work_order_and_avoids_duplicates(): void
    {
        $sales = User::factory()->sales()->create();

        $proposal = Proposal::factory()->approved()->create([
            'amount' => 2000,
            'sales_owner_id' => $sales->id,
            'created_by' => $sales->id,
            'updated_by' => $sales->id,
        ]);

        $this->actingAs($sales)
            ->post(route('proposals.convert-to-work-order', $proposal), [
                'create_down_payment_receivable' => 1,
                'down_payment_amount' => 300,
            ])
            ->assertRedirect();

        $workOrder = WorkOrder::query()->where('proposal_id', $proposal->id)->first();

        $this->assertNotNull($workOrder);
        $this->assertDatabaseHas('account_receivables', [
            'work_order_id' => $workOrder->id,
            'expected_amount' => 300,
        ]);

        $this->actingAs($sales)
            ->post(route('proposals.convert-to-work-order', $proposal), [
                'create_down_payment_receivable' => 1,
                'down_payment_amount' => 300,
            ])
            ->assertRedirect();

        $this->assertSame(1, WorkOrder::query()->where('proposal_id', $proposal->id)->count());
        $this->assertSame(1, AccountReceivable::query()->where('work_order_id', $workOrder->id)->count());
    }
}
