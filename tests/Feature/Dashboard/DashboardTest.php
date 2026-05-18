<?php

namespace Tests\Feature\Dashboard;

use App\Enums\PayableStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_finance_user_can_access_dashboard(): void
    {
        $finance = User::factory()->finance()->create();

        $this->actingAs($finance)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_operations_user_can_access_dashboard(): void
    {
        $ops = User::factory()->operations()->create();

        $this->actingAs($ops)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_sales_user_can_access_dashboard(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_with_empty_database(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $summaryCards = $response->viewData('summaryCards');
        $this->assertIsArray($summaryCards);
        $this->assertArrayHasKey('openWorkOrders', $summaryCards);
        $this->assertSame(0, $summaryCards['openWorkOrders']);
        $this->assertArrayHasKey('receivablesOpenTotal', $summaryCards);
        $this->assertSame(0.0, $summaryCards['receivablesOpenTotal']);
    }

    public function test_finance_role_sees_financial_summary_cards(): void
    {
        $finance = User::factory()->finance()->create();

        AccountReceivable::factory()->create([
            'status'          => ReceivableStatus::EXPECTED,
            'expected_amount' => 500.00,
            'expected_date'   => now()->addDays(5)->toDateString(),
            'created_by'      => $finance->id,
            'updated_by'      => $finance->id,
        ]);

        AccountReceivable::factory()->create([
            'status'          => ReceivableStatus::OVERDUE,
            'expected_amount' => 200.00,
            'expected_date'   => now()->subDays(5)->toDateString(),
            'created_by'      => $finance->id,
            'updated_by'      => $finance->id,
        ]);

        $response = $this->actingAs($finance)
            ->get(route('dashboard'))
            ->assertOk();

        $summaryCards = $response->viewData('summaryCards');
        $this->assertTrue($response->viewData('canSeeFinance'));
        $this->assertSame(500.0, $summaryCards['receivablesOpenTotal']);
        $this->assertSame(200.0, $summaryCards['receivablesOverdueTotal']);
    }

    public function test_operations_role_cannot_see_financial_data(): void
    {
        $ops = User::factory()->operations()->create();

        $response = $this->actingAs($ops)
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertFalse($response->viewData('canSeeFinance'));
    }

    public function test_overdue_payable_appears_in_attention_items(): void
    {
        $admin = User::factory()->admin()->create();

        AccountPayable::factory()->create([
            'status'     => PayableStatus::OVERDUE,
            'supplier'   => 'Fornecedor Urgente',
            'amount'     => 1500.00,
            'due_date'   => now()->subDays(3)->toDateString(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $attentionItems = $response->viewData('attentionItems');
        $this->assertNotEmpty($attentionItems);

        $types = array_column($attentionItems, 'type');
        $this->assertContains('payable_overdue', $types);
    }

    public function test_overdue_work_order_appears_in_critical_work_orders(): void
    {
        $admin = User::factory()->admin()->create();

        WorkOrder::factory()->create([
            'status'                => WorkOrderStatus::IN_PRODUCTION,
            'expected_delivery_date' => now()->subDays(2)->toDateString(),
            'created_by'            => $admin->id,
            'updated_by'            => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $criticalWOs = $response->viewData('criticalWorkOrders');
        $this->assertNotEmpty($criticalWOs);

        $overdue = array_filter($criticalWOs, static fn (array $wo): bool => $wo['is_overdue'] === true);
        $this->assertNotEmpty($overdue);
    }

    public function test_open_proposal_appears_in_sales_funnel(): void
    {
        $sales = User::factory()->sales()->create();

        Proposal::factory()->create([
            'status'         => ProposalStatus::SENT,
            'amount'         => 10000.00,
            'created_by'     => $sales->id,
            'updated_by'     => $sales->id,
            'sales_owner_id' => $sales->id,
        ]);

        $response = $this->actingAs($sales)
            ->get(route('dashboard'))
            ->assertOk();

        $funnel = $response->viewData('salesFunnel');
        $this->assertNotEmpty($funnel);

        $sentStage = collect($funnel)->firstWhere('status', ProposalStatus::SENT->value);
        $this->assertNotNull($sentStage);
        $this->assertSame(1, $sentStage['count']);
        $this->assertSame(10000.0, $sentStage['total']);
    }

    public function test_dashboard_summary_cards_contain_all_expected_keys(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $summaryCards = $response->viewData('summaryCards');

        $expectedKeys = [
            'receivablesOpenTotal', 'receivablesOverdueTotal',
            'payablesOpenTotal', 'payablesOverdueTotal', 'payablesDueNext7',
            'receivedThisMonth', 'paidThisMonth', 'projectedCashBalance',
            'openWorkOrders', 'overdueWorkOrders', 'nearDeliveryWorkOrders',
            'workOrdersWithoutResponsible', 'waitingDownPaymentWorkOrders', 'deliveredPendingFinalBalance',
            'estimatedOperationalMargin',
            'openProposals', 'negotiatingProposals', 'approvedThisMonth', 'lostThisMonth', 'weightedForecast',
            'activeCustomers', 'blockedCustomers', 'customersWithOpenBalance', 'customersWithOverdueCount',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $summaryCards, "Missing key: {$key}");
        }
    }

    public function test_category_string_bug_does_not_occur_on_dashboard(): void
    {
        $finance = User::factory()->finance()->create();

        AccountPayable::factory()->create([
            'status'     => PayableStatus::OPEN,
            'amount'     => 300.0,
            'due_date'   => now()->addDays(3)->toDateString(),
            'created_by' => $finance->id,
            'updated_by' => $finance->id,
        ]);

        // Ensure dashboard returns HTTP 200 without any 'category on string' error
        $this->actingAs($finance)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
