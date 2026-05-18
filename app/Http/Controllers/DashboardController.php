<?php

namespace App\Http\Controllers;

use App\Enums\AlertStatus;
use App\Enums\CustomerStatus;
use App\Enums\PayableStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Alert;
use App\Models\Customer;
use App\Models\Proposal;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $canSeeFinance = $user->can('viewAny', AccountReceivable::class)
            && $user->can('viewAny', AccountPayable::class);

        $customerVersion = Cache::get('dashboard_customer_version', 0);
        $financeVersion  = Cache::get('dashboard_finance_version', 0);
        $cacheKey = 'dashboard_v7_' . $user->id . '_' . ($canSeeFinance ? 'fin' : 'ops') . '_cv' . $customerVersion . '_fv' . $financeVersion;

        $cached = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($canSeeFinance): array {
            return [
                'summaryCards'       => $this->buildSummaryCards($canSeeFinance),
                'attentionItems'     => $this->buildAttentionItems($canSeeFinance),
                'upcomingDueItems'   => $canSeeFinance ? $this->buildUpcomingDueItems() : [],
                'criticalWorkOrders' => $this->buildCriticalWorkOrders(),
                'proposalFollowUps'  => $this->buildProposalFollowUps(),
                'customerData'       => $this->buildCustomerData($canSeeFinance),
                'cashFlowChart'      => $canSeeFinance ? $this->buildCashFlowChart() : [],
                'salesFunnel'        => $this->buildSalesFunnel(),
            ];
        });

        $priorityAlerts = Alert::query()
            ->whereIn('status', [AlertStatus::OPEN->value, AlertStatus::READ->value])
            ->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 0 WHEN 'WARNING' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', $cached + [
            'canSeeFinance'  => $canSeeFinance,
            'priorityAlerts' => $priorityAlerts,
        ]);
    }

    // Summary cards

    private function buildSummaryCards(bool $canSeeFinance): array
    {
        $cards = [
            'openWorkOrders'               => 0,
            'overdueWorkOrders'            => 0,
            'nearDeliveryWorkOrders'       => 0,
            'workOrdersWithoutResponsible' => 0,
            'waitingDownPaymentWorkOrders' => 0,
            'deliveredPendingFinalBalance' => 0,
            'estimatedOperationalMargin'   => 0.0,
            'openProposals'                => 0,
            'negotiatingProposals'         => 0,
            'approvedThisMonth'            => 0,
            'lostThisMonth'                => 0,
            'weightedForecast'             => 0.0,
            'activeCustomers'              => 0,
            'blockedCustomers'             => 0,
            'customersWithOpenBalance'     => 0,
            'customersWithOverdueCount'    => 0,
            'receivablesOpenTotal'         => 0.0,
            'receivablesOverdueTotal'      => 0.0,
            'payablesOpenTotal'            => 0.0,
            'payablesOverdueTotal'         => 0.0,
            'payablesDueNext7'             => 0.0,
            'receivedThisMonth'            => 0.0,
            'paidThisMonth'                => 0.0,
            'projectedCashBalance'         => 0.0,
            'actualCashBalance'            => 0.0,
        ];

        $deliveredVal  = WorkOrderStatus::DELIVERED->value;
        $cancelledVal  = WorkOrderStatus::CANCELLED->value;
        $waitingDpVal  = WorkOrderStatus::WAITING_DOWN_PAYMENT->value;
        $today         = now()->toDateString();
        $near3d        = now()->addDays(3)->toDateString();

        $woStats = WorkOrder::query()
            ->selectRaw(
                'SUM(CASE WHEN status NOT IN (?,?) THEN 1 ELSE 0 END) as open_count,'
                . 'SUM(CASE WHEN status NOT IN (?,?) AND responsible_user_id IS NULL THEN 1 ELSE 0 END) as no_responsible,'
                . 'SUM(CASE WHEN status NOT IN (?,?) AND expected_delivery_date < ? THEN 1 ELSE 0 END) as overdue_count,'
                . 'SUM(CASE WHEN status NOT IN (?,?) AND expected_delivery_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as near_delivery,'
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as waiting_dp,'
                . 'SUM(CASE WHEN status = ? AND receivable_balance > 0 THEN 1 ELSE 0 END) as delivered_pending,'
                . 'SUM(CASE WHEN status NOT IN (?,?) THEN COALESCE(contracted_amount,0) - COALESCE(estimated_cost,0) ELSE 0 END) as margin',
                [
                    $deliveredVal, $cancelledVal,
                    $deliveredVal, $cancelledVal,
                    $deliveredVal, $cancelledVal, $today,
                    $deliveredVal, $cancelledVal, $today, $near3d,
                    $waitingDpVal,
                    $deliveredVal,
                    $deliveredVal, $cancelledVal,
                ]
            )
            ->first();

        if ($woStats) {
            $cards['openWorkOrders']               = (int) $woStats->open_count;
            $cards['overdueWorkOrders']            = (int) $woStats->overdue_count;
            $cards['nearDeliveryWorkOrders']       = (int) $woStats->near_delivery;
            $cards['workOrdersWithoutResponsible'] = (int) $woStats->no_responsible;
            $cards['waitingDownPaymentWorkOrders'] = (int) $woStats->waiting_dp;
            $cards['deliveredPendingFinalBalance'] = (int) $woStats->delivered_pending;
            $cards['estimatedOperationalMargin']   = (float) ($woStats->margin ?? 0);
        }

        $monthStart = now()->startOfMonth()->toDateString();

        $proposalStats = Proposal::query()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent_count,'
                . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as negotiating_count,'
                . 'SUM(CASE WHEN status = ? AND DATE(updated_at) >= ? THEN 1 ELSE 0 END) as approved_month,'
                . 'SUM(CASE WHEN status = ? AND DATE(updated_at) >= ? THEN 1 ELSE 0 END) as lost_month,'
                . 'SUM(CASE WHEN status IN (?,?) THEN COALESCE(weighted_amount,0) ELSE 0 END) as weighted_total',
                [
                    ProposalStatus::SENT->value,
                    ProposalStatus::NEGOTIATING->value,
                    ProposalStatus::APPROVED->value, $monthStart,
                    ProposalStatus::LOST->value, $monthStart,
                    ProposalStatus::SENT->value, ProposalStatus::NEGOTIATING->value,
                ]
            )
            ->first();

        if ($proposalStats) {
            $cards['openProposals']       = (int) $proposalStats->sent_count;
            $cards['negotiatingProposals'] = (int) $proposalStats->negotiating_count;
            $cards['approvedThisMonth']   = (int) $proposalStats->approved_month;
            $cards['lostThisMonth']       = (int) $proposalStats->lost_month;
            $cards['weightedForecast']    = (float) ($proposalStats->weighted_total ?? 0);
        }

        if (Schema::hasTable('customers')) {
            $custStats = Customer::query()
                ->selectRaw(
                    'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_count,'
                    . 'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as blocked_count',
                    [CustomerStatus::ACTIVE->value, CustomerStatus::BLOCKED->value]
                )
                ->first();

            if ($custStats) {
                $cards['activeCustomers']  = (int) $custStats->active_count;
                $cards['blockedCustomers'] = (int) $custStats->blocked_count;
            }

            if (Schema::hasColumn('account_receivables', 'customer_id')) {
                $cards['customersWithOpenBalance'] = (int) AccountReceivable::query()
                    ->whereIn('status', [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value])
                    ->whereNotNull('customer_id')
                    ->distinct('customer_id')
                    ->count('customer_id');

                $cards['customersWithOverdueCount'] = (int) AccountReceivable::query()
                    ->where('status', ReceivableStatus::OVERDUE->value)
                    ->whereNotNull('customer_id')
                    ->distinct('customer_id')
                    ->count('customer_id');
            }
        }

        if ($canSeeFinance) {
            $recStats = AccountReceivable::query()
                ->selectRaw(
                    'SUM(CASE WHEN status = ? THEN expected_amount ELSE 0 END) as expected_total,'
                    . 'SUM(CASE WHEN status = ? THEN expected_amount ELSE 0 END) as overdue_total',
                    [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value]
                )
                ->first();

            $payStats = AccountPayable::query()
                ->selectRaw(
                    'SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as open_total,'
                    . 'SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as overdue_total',
                    [PayableStatus::OPEN->value, PayableStatus::OVERDUE->value]
                )
                ->first();

            $cards['receivablesOpenTotal']    = (float) ($recStats->expected_total ?? 0);
            $cards['receivablesOverdueTotal'] = (float) ($recStats->overdue_total ?? 0);
            $cards['payablesOpenTotal']        = (float) ($payStats->open_total ?? 0);
            $cards['payablesOverdueTotal']     = (float) ($payStats->overdue_total ?? 0);

            $cards['payablesDueNext7'] = (float) AccountPayable::query()
                ->whereIn('status', [PayableStatus::OPEN->value, PayableStatus::OVERDUE->value])
                ->whereBetween('due_date', [$today, now()->addDays(7)->toDateString()])
                ->sum('amount');

            $cards['receivedThisMonth'] = (float) AccountReceivable::query()
                ->where('status', ReceivableStatus::RECEIVED->value)
                ->whereDate('received_date', '>=', $monthStart)
                ->sum('received_amount');

            $cards['paidThisMonth'] = (float) AccountPayable::query()
                ->where('status', PayableStatus::PAID->value)
                ->whereDate('payment_date', '>=', $monthStart)
                ->sum('paid_amount');

            $openNetReceivable = $cards['receivablesOpenTotal'] + $cards['receivablesOverdueTotal'];
            $openNetPayable    = $cards['payablesOpenTotal'] + $cards['payablesOverdueTotal'];
            $cards['projectedCashBalance'] = $openNetReceivable - $openNetPayable;

            $totalReceived = (float) AccountReceivable::query()
                ->where('status', ReceivableStatus::RECEIVED->value)
                ->sum('received_amount');

            $totalPaid = (float) AccountPayable::query()
                ->where('status', PayableStatus::PAID->value)
                ->sum('paid_amount');

            $initialBalance = (float) \App\Models\Setting::get('cash_balance', 0);
            $cards['actualCashBalance'] = $initialBalance + $totalReceived - $totalPaid;
        }

        return $cards;
    }

    // Attention items

    private function buildAttentionItems(bool $canSeeFinance): array
    {
        $items = [];

        if ($canSeeFinance) {
            $result = AccountPayable::query()
                ->where('status', PayableStatus::OVERDUE->value)
                ->selectRaw('COUNT(*) as cnt, SUM(amount) as total')
                ->first();

            if ($result && $result->cnt > 0) {
                $items[] = [
                    'type'         => 'payable_overdue',
                    'severity'     => 'CRITICAL',
                    'title'        => $result->cnt . ($result->cnt === 1 ? ' conta vencida' : ' contas vencidas'),
                    'description'  => 'Contas a pagar com prazo expirado aguardando quitacao.',
                    'amount'       => (float) $result->total,
                    'date'         => null,
                    'route_name'   => 'payables.index',
                    'action_label' => 'Ver contas',
                ];
            }

            $result = AccountReceivable::query()
                ->where('status', ReceivableStatus::OVERDUE->value)
                ->selectRaw('COUNT(*) as cnt, SUM(expected_amount) as total')
                ->first();

            if ($result && $result->cnt > 0) {
                $items[] = [
                    'type'         => 'receivable_overdue',
                    'severity'     => 'CRITICAL',
                    'title'        => $result->cnt . ($result->cnt === 1 ? ' recebivel vencido' : ' recebiveis vencidos'),
                    'description'  => 'Recebiveis com prazo expirado ainda nao recebidos.',
                    'amount'       => (float) $result->total,
                    'date'         => null,
                    'route_name'   => 'receivables.index',
                    'action_label' => 'Ver recebiveis',
                ];
            }
        }

        $lateWOs = WorkOrder::query()
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->whereDate('expected_delivery_date', '<', now()->toDateString())
            ->count();

        if ($lateWOs > 0) {
            $items[] = [
                'type'         => 'work_order_late',
                'severity'     => 'CRITICAL',
                'title'        => $lateWOs . ($lateWOs === 1 ? ' OS atrasada' : ' OS atrasadas'),
                'description'  => 'Ordens de servico com prazo de entrega vencido.',
                'amount'       => null,
                'date'         => null,
                'route_name'   => 'work-orders.index',
                'action_label' => 'Ver OS',
            ];
        }

        $noResponsible = WorkOrder::query()
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->whereNull('responsible_user_id')
            ->count();

        if ($noResponsible > 0) {
            $items[] = [
                'type'         => 'work_order_no_responsible',
                'severity'     => 'WARNING',
                'title'        => $noResponsible . ' OS sem responsavel',
                'description'  => 'Ordens de servico sem usuario responsavel atribuido.',
                'amount'       => null,
                'date'         => null,
                'route_name'   => 'work-orders.index',
                'action_label' => 'Atribuir responsavel',
            ];
        }

        $deliveredPending = WorkOrder::query()
            ->where('status', WorkOrderStatus::DELIVERED->value)
            ->where('receivable_balance', '>', 0)
            ->count();

        if ($deliveredPending > 0) {
            $items[] = [
                'type'         => 'work_order_pending_balance',
                'severity'     => 'WARNING',
                'title'        => $deliveredPending . ($deliveredPending === 1 ? ' OS entregue com saldo' : ' OS entregues com saldo'),
                'description'  => 'Ordens entregues com saldo financeiro a receber.',
                'amount'       => null,
                'date'         => null,
                'route_name'   => 'work-orders.index',
                'action_label' => 'Ver OS',
            ];
        }

        if (Schema::hasTable('customers')) {
            $blocked = Customer::query()->where('status', CustomerStatus::BLOCKED->value)->count();
            if ($blocked > 0) {
                $items[] = [
                    'type'         => 'customer_blocked',
                    'severity'     => 'WARNING',
                    'title'        => $blocked . ($blocked === 1 ? ' cliente bloqueado' : ' clientes bloqueados'),
                    'description'  => 'Clientes com status bloqueado que podem requerer revisao.',
                    'amount'       => null,
                    'date'         => null,
                    'route_name'   => 'customers.index',
                    'action_label' => 'Ver clientes',
                ];
            }
        }

        usort($items, static fn ($a, $b): int =>
            ($a['severity'] === 'CRITICAL' ? 0 : 1) <=> ($b['severity'] === 'CRITICAL' ? 0 : 1)
        );

        return $items;
    }

    // Upcoming due items

    private function buildUpcomingDueItems(): array
    {
        $items = [];
        $today  = now()->toDateString();
        $end30d = now()->addDays(30)->toDateString();

        $payables = AccountPayable::query()
            ->whereIn('status', [PayableStatus::OPEN->value, PayableStatus::OVERDUE->value])
            ->whereBetween('due_date', [$today, $end30d])
            ->orderBy('due_date')
            ->limit(15)
            ->get(['id', 'supplier', 'description', 'due_date', 'amount', 'status']);

        foreach ($payables as $row) {
            $items[] = [
                'type'         => 'payable',
                'name'         => $row->supplier ?: $row->description,
                'date'         => $row->due_date?->toDateString(),
                'amount'       => (float) $row->amount,
                'status'       => $row->status->value,
                'status_label' => $row->status->label(),
                'route_name'   => 'payables.edit',
                'route_id'     => $row->id,
            ];
        }

        $receivables = AccountReceivable::query()
            ->whereIn('status', [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value])
            ->whereBetween('expected_date', [$today, $end30d])
            ->orderBy('expected_date')
            ->limit(15)
            ->get(['id', 'customer', 'expected_date', 'expected_amount', 'status']);

        foreach ($receivables as $row) {
            $items[] = [
                'type'         => 'receivable',
                'name'         => $row->customer,
                'date'         => $row->expected_date?->toDateString(),
                'amount'       => (float) $row->expected_amount,
                'status'       => $row->status->value,
                'status_label' => $row->status->label(),
                'route_name'   => 'receivables.edit',
                'route_id'     => $row->id,
            ];
        }

        usort($items, static fn ($a, $b): int => strcmp($a['date'] ?? '', $b['date'] ?? ''));

        return array_slice($items, 0, 20);
    }

    // Critical work orders

    private function buildCriticalWorkOrders(): array
    {
        $deliveredVal = WorkOrderStatus::DELIVERED->value;
        $cancelledVal = WorkOrderStatus::CANCELLED->value;
        $today        = now()->toDateString();
        $near7d       = now()->addDays(7)->toDateString();
        $near3d       = now()->addDays(3)->toDateString();

        $workOrders = WorkOrder::query()
            ->whereNotIn('status', [$deliveredVal, $cancelledVal])
            ->where(function ($q) use ($today, $near7d): void {
                $q->whereDate('expected_delivery_date', '<=', $near7d)
                  ->orWhereNull('responsible_user_id')
                  ->orWhere('status', WorkOrderStatus::WAITING_DOWN_PAYMENT->value);
            })
            ->with(['responsibleUser:id,name', 'customerRef:id,name'])
            ->orderByRaw(
                'CASE WHEN expected_delivery_date < ? THEN 0'
                . ' WHEN expected_delivery_date <= ? THEN 1'
                . ' WHEN responsible_user_id IS NULL THEN 2 ELSE 3 END',
                [$today, $near3d]
            )
            ->limit(10)
            ->get([
                'id', 'number', 'customer', 'customer_id', 'responsible_user_id',
                'status', 'expected_delivery_date', 'recalculated_delivery_date',
                'progress_percentage', 'receivable_balance',
            ]);

        return $workOrders->map(static function (WorkOrder $wo): array {
            $deadline   = $wo->deliveryDeadline();
            $daysLeft   = $deadline ? (int) now()->startOfDay()->diffInDays($deadline, false) : null;

            return [
                'id'                  => $wo->id,
                'number'              => $wo->number,
                'customer'            => $wo->customerRef?->name ?? $wo->customer ?? '-',
                'customer_id'         => $wo->customer_id,
                'responsible'         => $wo->responsibleUser?->name,
                'status'              => $wo->status->value,
                'status_label'        => $wo->status->label(),
                'delivery_date'       => $deadline?->toDateString(),
                'delivery_days_left'  => $daysLeft,
                'progress_percentage' => (int) ($wo->progress_percentage ?? 0),
                'receivable_balance'  => (float) ($wo->receivable_balance ?? 0),
                'is_overdue'          => $wo->isDeliveryOverdue(),
                'is_near_delivery'    => $wo->isNearDelivery(),
                'has_no_responsible'  => $wo->responsible_user_id === null,
                'is_waiting_down_pay' => $wo->status === WorkOrderStatus::WAITING_DOWN_PAYMENT,
            ];
        })->all();
    }

    // Proposal follow-ups

    private function buildProposalFollowUps(): array
    {
        $proposals = Proposal::query()
            ->whereIn('status', [ProposalStatus::SENT->value, ProposalStatus::NEGOTIATING->value])
            ->with(['salesOwner:id,name', 'customerRef:id,name'])
            ->orderByRaw('CASE WHEN expected_close_date IS NULL THEN 1 ELSE 0 END, expected_close_date ASC')
            ->limit(8)
            ->get([
                'id', 'number', 'customer', 'customer_id', 'sales_owner_id',
                'amount', 'weighted_amount', 'close_probability', 'expected_close_date',
                'status', 'sent_at',
            ]);

        return $proposals->map(static function (Proposal $p): array {
            $daysToClose   = $p->expected_close_date
                ? (int) now()->startOfDay()->diffInDays($p->expected_close_date, false)
                : null;
            $daysSinceSent = $p->sent_at ? (int) $p->sent_at->diffInDays(now()) : null;

            return [
                'id'                  => $p->id,
                'number'              => $p->number,
                'customer'            => $p->customerRef?->name ?? $p->customer ?? '-',
                'customer_id'         => $p->customer_id,
                'sales_owner'         => $p->salesOwner?->name,
                'amount'              => (float) $p->amount,
                'weighted_amount'     => (float) $p->weighted_amount,
                'close_probability'   => (int) $p->close_probability,
                'expected_close_date' => $p->expected_close_date?->toDateString(),
                'days_to_close'       => $daysToClose,
                'days_since_sent'     => $daysSinceSent,
                'status'              => $p->status->value,
                'status_label'        => $p->status->label(),
                'is_overdue'          => $p->expected_close_date && $p->expected_close_date->lt(now()),
            ];
        })->all();
    }

    // Customer data

    private function buildCustomerData(bool $canSeeFinance): array
    {
        $data = [
            'topOpenBalanceCustomers' => [],
            'customersWithOverdue'    => [],
            'recentCustomers'         => [],
        ];

        if (!Schema::hasTable('customers')) {
            return $data;
        }

        if ($canSeeFinance && Schema::hasColumn('account_receivables', 'customer_id')) {
            $topOpen = Customer::query()
                ->select('customers.id', 'customers.name')
                ->selectRaw('SUM(account_receivables.expected_amount) as open_total')
                ->join('account_receivables', 'account_receivables.customer_id', '=', 'customers.id')
                ->whereIn('account_receivables.status', [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value])
                ->groupBy('customers.id', 'customers.name')
                ->orderByDesc('open_total')
                ->limit(8)
                ->get();

            $data['topOpenBalanceCustomers'] = $topOpen->map(static fn ($c): array => [
                'id'         => $c->id,
                'name'       => $c->name,
                'open_total' => (float) $c->open_total,
            ])->all();

            $overdue = Customer::query()
                ->select('customers.id', 'customers.name')
                ->selectRaw('SUM(account_receivables.expected_amount) as overdue_total')
                ->join('account_receivables', 'account_receivables.customer_id', '=', 'customers.id')
                ->where('account_receivables.status', ReceivableStatus::OVERDUE->value)
                ->groupBy('customers.id', 'customers.name')
                ->orderByDesc('overdue_total')
                ->limit(5)
                ->get();

            $data['customersWithOverdue'] = $overdue->map(static fn ($c): array => [
                'id'            => $c->id,
                'name'          => $c->name,
                'overdue_total' => (float) $c->overdue_total,
            ])->all();
        }

        $data['recentCustomers'] = Customer::query()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'status', 'created_at'])
            ->map(static fn ($c): array => [
                'id'           => $c->id,
                'name'         => $c->name,
                'status'       => $c->status->value,
                'status_label' => $c->status->label(),
                'created_at'   => $c->created_at?->format('d/m/Y'),
            ])->all();

        return $data;
    }

    // Cash flow chart

    private function buildCashFlowChart(): array
    {
        $startMonth      = now()->copy()->startOfMonth()->subMonths(5);
        $monthKeys       = [];
        $months          = [];
        $receivedByMonth = [];
        $expenseByMonth  = [];

        for ($i = 0; $i < 6; $i++) {
            $date = $startMonth->copy()->addMonths($i);
            $key  = $date->format('Y-m');
            $monthKeys[]       = $key;
            $months[]          = ['key' => $key, 'label' => strtoupper($date->format('M'))];
            $receivedByMonth[$key] = 0.0;
            $expenseByMonth[$key]  = 0.0;
        }

        AccountReceivable::query()
            ->where('status', ReceivableStatus::RECEIVED->value)
            ->whereDate('received_date', '>=', $startMonth->toDateString())
            ->get(['received_date', 'received_amount'])
            ->each(function ($row) use (&$receivedByMonth): void {
                if ($row->received_date !== null) {
                    $key = $row->received_date->format('Y-m');
                    if (isset($receivedByMonth[$key])) {
                        $receivedByMonth[$key] += (float) $row->received_amount;
                    }
                }
            });

        AccountPayable::query()
            ->where('status', PayableStatus::PAID->value)
            ->whereDate('payment_date', '>=', $startMonth->toDateString())
            ->get(['payment_date', 'paid_amount', 'amount'])
            ->each(function ($row) use (&$expenseByMonth): void {
                if ($row->payment_date !== null) {
                    $key = $row->payment_date->format('Y-m');
                    if (isset($expenseByMonth[$key])) {
                        $expenseByMonth[$key] += (float) ($row->paid_amount ?? $row->amount ?? 0);
                    }
                }
            });

        $maxValue = max(1.0, max($receivedByMonth), max($expenseByMonth));
        $rows     = [];

        foreach ($months as $month) {
            $k       = $month['key'];
            $income  = $receivedByMonth[$k] ?? 0.0;
            $expense = $expenseByMonth[$k] ?? 0.0;

            $rows[] = [
                'key'         => $k,
                'label'       => $month['label'],
                'income'      => $income,
                'expense'     => $expense,
                'net'         => $income - $expense,
                'income_pct'  => max(4, (int) (($income / $maxValue) * 100)),
                'expense_pct' => max(4, (int) (($expense / $maxValue) * 100)),
            ];
        }

        return ['rows' => $rows, 'maxValue' => $maxValue];
    }

    // Sales funnel

    private function buildSalesFunnel(): array
    {
        $statuses = [
            ProposalStatus::SENT->value,
            ProposalStatus::NEGOTIATING->value,
            ProposalStatus::APPROVED->value,
            ProposalStatus::LOST->value,
        ];

        $rows = Proposal::query()
            ->selectRaw('status, COUNT(*) as cnt, SUM(amount) as total')
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return collect($statuses)->map(static function (string $status) use ($rows): array {
            $row = $rows[$status] ?? null;

            return [
                'status'       => $status,
                'status_label' => ProposalStatus::from($status)->label(),
                'count'        => $row ? (int) $row->cnt : 0,
                'total'        => $row ? (float) $row->total : 0.0,
            ];
        })->all();
    }
}
