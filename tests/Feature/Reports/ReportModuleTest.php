<?php

namespace Tests\Feature\Reports;

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

class ReportModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_can_preview_and_export_financial_reports(): void
    {
        $finance = User::factory()->finance()->create();

        AccountPayable::factory()->create([
            'supplier' => 'Fornecedor A',
            'status' => PayableStatus::OPEN,
            'due_date' => now()->toDateString(),
            'amount' => 120,
            'created_by' => $finance->id,
            'updated_by' => $finance->id,
        ]);

        AccountReceivable::factory()->create([
            'customer' => 'Cliente A',
            'status' => ReceivableStatus::EXPECTED,
            'expected_date' => now()->toDateString(),
            'expected_amount' => 300,
            'created_by' => $finance->id,
            'updated_by' => $finance->id,
        ]);

        $financialView = $this->actingAs($finance)
            ->get(route('reports.financial', [
                'report' => 'payables_by_period',
                'date_from' => now()->subDays(2)->toDateString(),
                'date_to' => now()->addDays(2)->toDateString(),
            ]))
            ->assertOk()
            ->viewData('report');

        $this->assertSame('Contas a pagar por periodo', $financialView['title']);
        $this->assertSame(120.0, (float) $financialView['totals']['amount']);

        $this->actingAs($finance)
            ->get(route('reports.financial.export', [
                'report' => 'receivables_by_period',
                'date_from' => now()->subDays(2)->toDateString(),
                'date_to' => now()->addDays(2)->toDateString(),
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_view_margin_by_work_order_report(): void
    {
        $admin = User::factory()->admin()->create();

        WorkOrder::factory()->create([
            'status' => WorkOrderStatus::IN_PRODUCTION,
            'opened_at' => now()->toDateString(),
            'contracted_amount' => 2000,
            'actual_cost' => 1000,
            'actual_margin' => 1000,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $report = $this->actingAs($admin)
            ->get(route('reports.operational', [
                'report' => 'margin_by_work_order',
                'date_from' => now()->subDays(2)->toDateString(),
                'date_to' => now()->addDays(2)->toDateString(),
            ]))
            ->assertOk()
            ->viewData('report');

        $this->assertSame('Margem por ordem de servico', $report['title']);
        $this->assertSame(1000.0, (float) $report['totals']['actual_margin']);
    }

    public function test_sales_can_export_proposal_reports_only(): void
    {
        $sales = User::factory()->sales()->create();

        Proposal::factory()->create([
            'status' => ProposalStatus::SENT,
            'sales_owner_id' => $sales->id,
            'created_by' => $sales->id,
            'updated_by' => $sales->id,
        ]);

        $this->actingAs($sales)
            ->get(route('reports.sales', ['report' => 'open_proposals']))
            ->assertOk();

        $this->actingAs($sales)
            ->get(route('reports.sales.export', ['report' => 'open_proposals']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $this->actingAs($sales)
            ->get(route('reports.financial'))
            ->assertForbidden();
    }

    public function test_finance_cannot_access_sales_reports(): void
    {
        $finance = User::factory()->finance()->create();

        $this->actingAs($finance)
            ->get(route('reports.sales'))
            ->assertForbidden();
    }
}
