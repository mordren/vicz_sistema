<?php

namespace Tests\Feature\Policies;

use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Covers the permission matrix for all 8 policies.
 *
 * Convention: assertTrue(Gate::forUser($user)->allows(...))
 *             assertFalse(Gate::forUser($user)->allows(...))
 */
class PolicyTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // AccountPayablePolicy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_can_manage_payables(): void
    {
        $admin = User::factory()->admin()->create();
        $payable = AccountPayable::factory()->create();

        $this->assertTrue($admin->can('viewAny', AccountPayable::class));
        $this->assertTrue($admin->can('view', $payable));
        $this->assertTrue($admin->can('create', AccountPayable::class));
        $this->assertTrue($admin->can('update', $payable));
        $this->assertTrue($admin->can('delete', $payable));
        $this->assertTrue($admin->can('markAsPaid', $payable));
    }

    public function test_finance_can_manage_payables_but_not_delete(): void
    {
        $finance = User::factory()->finance()->create();
        $payable = AccountPayable::factory()->create();

        $this->assertTrue($finance->can('viewAny', AccountPayable::class));
        $this->assertTrue($finance->can('create', AccountPayable::class));
        $this->assertTrue($finance->can('update', $payable));
        $this->assertTrue($finance->can('markAsPaid', $payable));
        $this->assertFalse($finance->can('delete', $payable));
    }

    public function test_operations_cannot_access_payables(): void
    {
        $ops = User::factory()->operations()->create();
        $payable = AccountPayable::factory()->create();

        $this->assertFalse($ops->can('viewAny', AccountPayable::class));
        $this->assertFalse($ops->can('view', $payable));
        $this->assertFalse($ops->can('create', AccountPayable::class));
    }

    public function test_sales_cannot_access_payables(): void
    {
        $sales = User::factory()->sales()->create();
        $payable = AccountPayable::factory()->create();

        $this->assertFalse($sales->can('viewAny', AccountPayable::class));
        $this->assertFalse($sales->can('create', AccountPayable::class));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AccountReceivablePolicy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_can_manage_receivables(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = AccountReceivable::factory()->create();

        $this->assertTrue($admin->can('viewAny', AccountReceivable::class));
        $this->assertTrue($admin->can('delete', $receivable));
        $this->assertTrue($admin->can('markAsReceived', $receivable));
    }

    public function test_finance_can_manage_receivables_but_not_delete(): void
    {
        $finance = User::factory()->finance()->create();
        $receivable = AccountReceivable::factory()->create();

        $this->assertTrue($finance->can('viewAny', AccountReceivable::class));
        $this->assertTrue($finance->can('markAsReceived', $receivable));
        $this->assertFalse($finance->can('delete', $receivable));
    }

    public function test_operations_cannot_access_receivables(): void
    {
        $ops = User::factory()->operations()->create();
        $this->assertFalse($ops->can('viewAny', AccountReceivable::class));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WorkOrderPolicy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_all_roles_can_view_work_orders(): void
    {
        $workOrder = WorkOrder::factory()->create();

        foreach (['admin', 'finance', 'operations', 'sales'] as $role) {
            $user = User::factory()->{$role}()->create();
            $this->assertTrue($user->can('view', $workOrder), "Role {$role} should view work orders");
        }
    }

    public function test_operations_can_create_and_update_work_orders(): void
    {
        $ops = User::factory()->operations()->create();
        $workOrder = WorkOrder::factory()->create(['responsible_user_id' => $ops->id]);

        $this->assertTrue($ops->can('create', WorkOrder::class));
        $this->assertTrue($ops->can('update', $workOrder));
        $this->assertFalse($ops->can('delete', $workOrder));
    }

    public function test_operations_can_only_update_status_of_own_work_orders(): void
    {
        $ops1 = User::factory()->operations()->create();
        $ops2 = User::factory()->operations()->create();
        $workOrder = WorkOrder::factory()->create(['responsible_user_id' => $ops1->id]);

        $this->assertTrue($ops1->can('updateStatus', $workOrder));
        $this->assertFalse($ops2->can('updateStatus', $workOrder));
    }

    public function test_finance_and_sales_cannot_create_work_orders(): void
    {
        $finance = User::factory()->finance()->create();
        $sales = User::factory()->sales()->create();

        $this->assertFalse($finance->can('create', WorkOrder::class));
        $this->assertFalse($sales->can('create', WorkOrder::class));
    }

    public function test_only_admin_can_delete_work_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $ops = User::factory()->operations()->create();
        $workOrder = WorkOrder::factory()->create();

        $this->assertTrue($admin->can('delete', $workOrder));
        $this->assertFalse($ops->can('delete', $workOrder));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ProposalPolicy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_sales_can_manage_own_proposals(): void
    {
        $sales = User::factory()->sales()->create();
        $proposal = Proposal::factory()->create(['sales_owner_id' => $sales->id]);

        $this->assertTrue($sales->can('view', $proposal));
        $this->assertTrue($sales->can('create', Proposal::class));
        $this->assertTrue($sales->can('update', $proposal));
        $this->assertFalse($sales->can('delete', $proposal));
    }

    public function test_sales_cannot_update_other_owners_proposal(): void
    {
        $sales1 = User::factory()->sales()->create();
        $sales2 = User::factory()->sales()->create();
        $proposal = Proposal::factory()->create(['sales_owner_id' => $sales1->id]);

        $this->assertFalse($sales2->can('update', $proposal));
    }

    public function test_operations_cannot_access_proposals(): void
    {
        $ops = User::factory()->operations()->create();
        $proposal = Proposal::factory()->create();

        $this->assertFalse($ops->can('viewAny', Proposal::class));
        $this->assertFalse($ops->can('create', Proposal::class));
    }

    public function test_finance_can_view_but_not_create_proposals(): void
    {
        $finance = User::factory()->finance()->create();
        $proposal = Proposal::factory()->create();

        $this->assertTrue($finance->can('viewAny', Proposal::class));
        $this->assertTrue($finance->can('view', $proposal));
        $this->assertFalse($finance->can('create', Proposal::class));
        $this->assertFalse($finance->can('update', $proposal));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AlertPolicy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_all_roles_can_view_and_interact_with_alerts(): void
    {
        $alert = Alert::factory()->create();

        foreach (['admin', 'finance', 'operations', 'sales'] as $role) {
            $user = User::factory()->{$role}()->create();
            $this->assertTrue($user->can('view', $alert), "Role {$role} should view alerts");
            $this->assertTrue($user->can('markRead', $alert), "Role {$role} should mark alert as read");
            $this->assertTrue($user->can('resolve', $alert), "Role {$role} should resolve alert");
        }
    }

    public function test_only_admin_can_create_and_delete_alerts(): void
    {
        $admin = User::factory()->admin()->create();
        $finance = User::factory()->finance()->create();
        $ops = User::factory()->operations()->create();
        $sales = User::factory()->sales()->create();
        $alert = Alert::factory()->create();

        $this->assertTrue($admin->can('create', Alert::class));
        $this->assertTrue($admin->can('delete', $alert));

        $this->assertFalse($finance->can('create', Alert::class));
        $this->assertFalse($ops->can('create', Alert::class));
        $this->assertFalse($sales->can('create', Alert::class));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AuditLogPolicy
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_and_finance_can_view_audit_logs(): void
    {
        $auditLog = AuditLog::factory()->create();
        $admin = User::factory()->admin()->create();
        $finance = User::factory()->finance()->create();

        $this->assertTrue($admin->can('viewAny', AuditLog::class));
        $this->assertTrue($admin->can('view', $auditLog));
        $this->assertTrue($finance->can('viewAny', AuditLog::class));
    }

    public function test_operations_and_sales_cannot_view_audit_logs(): void
    {
        $ops = User::factory()->operations()->create();
        $sales = User::factory()->sales()->create();

        $this->assertFalse($ops->can('viewAny', AuditLog::class));
        $this->assertFalse($sales->can('viewAny', AuditLog::class));
    }

    public function test_audit_logs_are_immutable_via_policy(): void
    {
        $admin = User::factory()->admin()->create();
        $auditLog = AuditLog::factory()->create();

        $this->assertFalse($admin->can('create', AuditLog::class));
        $this->assertFalse($admin->can('update', $auditLog));
        $this->assertFalse($admin->can('delete', $auditLog));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ReportPolicy (via Gates)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_report_gate_financial_allows_admin_and_finance_only(): void
    {
        $admin = User::factory()->admin()->create();
        $finance = User::factory()->finance()->create();
        $ops = User::factory()->operations()->create();
        $sales = User::factory()->sales()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('report.viewFinancial'));
        $this->assertTrue(Gate::forUser($finance)->allows('report.viewFinancial'));
        $this->assertFalse(Gate::forUser($ops)->allows('report.viewFinancial'));
        $this->assertFalse(Gate::forUser($sales)->allows('report.viewFinancial'));
    }

    public function test_report_gate_operational_allows_admin_and_operations_only(): void
    {
        $admin = User::factory()->admin()->create();
        $finance = User::factory()->finance()->create();
        $ops = User::factory()->operations()->create();
        $sales = User::factory()->sales()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('report.viewOperational'));
        $this->assertTrue(Gate::forUser($ops)->allows('report.viewOperational'));
        $this->assertFalse(Gate::forUser($finance)->allows('report.viewOperational'));
        $this->assertFalse(Gate::forUser($sales)->allows('report.viewOperational'));
    }

    public function test_report_gate_sales_allows_admin_and_sales_only(): void
    {
        $admin = User::factory()->admin()->create();
        $finance = User::factory()->finance()->create();
        $sales = User::factory()->sales()->create();
        $ops = User::factory()->operations()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('report.viewSales'));
        $this->assertTrue(Gate::forUser($sales)->allows('report.viewSales'));
        $this->assertFalse(Gate::forUser($finance)->allows('report.viewSales'));
        $this->assertFalse(Gate::forUser($ops)->allows('report.viewSales'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Route-level middleware enforcement
    // ─────────────────────────────────────────────────────────────────────────

    public function test_operations_user_gets_403_on_payables_route(): void
    {
        $ops = User::factory()->operations()->create();

        $this->actingAs($ops)->get(route('payables.index'))->assertForbidden();
    }

    public function test_sales_user_gets_403_on_payables_route(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)->get(route('payables.index'))->assertForbidden();
    }

    public function test_finance_user_gets_403_on_work_orders_create_route(): void
    {
        $finance = User::factory()->finance()->create();

        $this->actingAs($finance)->get(route('work-orders.create'))->assertForbidden();
    }

    public function test_operations_user_gets_403_on_proposals_create_route(): void
    {
        $ops = User::factory()->operations()->create();

        $this->actingAs($ops)->get(route('proposals.create'))->assertForbidden();
    }
}
