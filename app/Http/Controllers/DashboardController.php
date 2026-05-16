<?php

namespace App\Http\Controllers;

use App\Enums\AlertStatus;
use App\Enums\CustomerStatus;
use App\Enums\PayableStatus;
use App\Enums\ReceivableStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Alert;
use App\Models\Customer;
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

        $dashboardData = Cache::remember(
            'dashboard_reports_v3_'.$user->id.'_'.($canSeeFinance ? 'finance' : 'restricted'),
            now()->addSeconds(30),
            function () use ($canSeeFinance): array {
                $startMonth = now()->copy()->startOfMonth()->subMonths(5);
                $monthKeys = [];
                $months = [];

                for ($i = 0; $i < 6; $i++) {
                    $date = $startMonth->copy()->addMonths($i);
                    $key = $date->format('Y-m');
                    $monthKeys[] = $key;
                    $months[] = [
                        'key' => $key,
                        'label' => strtoupper($date->format('M')),
                    ];
                }

                $receivedByMonth = array_fill_keys($monthKeys, 0.0);
                $expenseByMonth = array_fill_keys($monthKeys, 0.0);

                $pendingReceivables = 0.0;
                $overdueReceivables = 0.0;
                $recentReceivables = collect();
                $expenseCategories = collect();

                if ($canSeeFinance) {
                    $receivablesByStatus = AccountReceivable::query()
                        ->selectRaw('status, SUM(expected_amount) as total')
                        ->whereIn('status', [ReceivableStatus::EXPECTED, ReceivableStatus::OVERDUE])
                        ->groupBy('status')
                        ->pluck('total', 'status');

                    $pendingReceivables = (float) ($receivablesByStatus[ReceivableStatus::EXPECTED->value] ?? 0);
                    $overdueReceivables = (float) ($receivablesByStatus[ReceivableStatus::OVERDUE->value] ?? 0);

                    $receivedRows = AccountReceivable::query()
                        ->where('status', ReceivableStatus::RECEIVED)
                        ->whereDate('received_date', '>=', $startMonth->toDateString())
                        ->get(['received_date', 'received_amount']);

                    foreach ($receivedRows as $row) {
                        if ($row->received_date === null) {
                            continue;
                        }

                        $key = $row->received_date->format('Y-m');
                        if (isset($receivedByMonth[$key])) {
                            $receivedByMonth[$key] += (float) $row->received_amount;
                        }
                    }

                    $expenseRows = AccountPayable::query()
                        ->where('status', PayableStatus::PAID)
                        ->whereDate('payment_date', '>=', $startMonth->toDateString())
                        ->get(['payment_date', 'paid_amount', 'amount']);

                    foreach ($expenseRows as $row) {
                        if ($row->payment_date === null) {
                            continue;
                        }

                        $key = $row->payment_date->format('Y-m');
                        if (isset($expenseByMonth[$key])) {
                            $expenseByMonth[$key] += (float) ($row->paid_amount ?? $row->amount ?? 0);
                        }
                    }

                    $expenseCategories = AccountPayable::query()
                        ->selectRaw('category, SUM(COALESCE(paid_amount, amount)) as total')
                        ->whereIn('status', [PayableStatus::PAID, PayableStatus::OPEN, PayableStatus::OVERDUE])
                        ->groupBy('category')
                        ->orderByDesc('total')
                        ->limit(4)
                        ->get();

                    $recentReceivables = AccountReceivable::query()
                        ->with('workOrder:id,number')
                        ->orderByRaw('COALESCE(received_date, expected_date) DESC')
                        ->limit(6)
                        ->get();
                }

                $maxChart = max(1, max($receivedByMonth), max($expenseByMonth));
                $totalFlow = $pendingReceivables + $overdueReceivables;
                $predictabilityScore = $totalFlow > 0
                    ? max(55, min(100, 100 - (($overdueReceivables / $totalFlow) * 100)))
                    : 100;

                $currentMonthKey = now()->format('Y-m');
                $previousMonthKey = now()->copy()->subMonth()->format('Y-m');
                $currentNet = ($receivedByMonth[$currentMonthKey] ?? 0) - ($expenseByMonth[$currentMonthKey] ?? 0);
                $previousNet = ($receivedByMonth[$previousMonthKey] ?? 0) - ($expenseByMonth[$previousMonthKey] ?? 0);
                $variation = $previousNet != 0
                    ? (($currentNet - $previousNet) / abs($previousNet)) * 100
                    : 0;

                return [
                    'months' => $months,
                    'receivedByMonth' => $receivedByMonth,
                    'expenseByMonth' => $expenseByMonth,
                    'maxChart' => $maxChart,
                    'pendingReceivables' => $pendingReceivables,
                    'overdueReceivables' => $overdueReceivables,
                    'predictabilityScore' => round($predictabilityScore, 1),
                    'variation' => round($variation, 1),
                    'expenseCategories' => $expenseCategories,
                    'recentReceivables' => $recentReceivables,
                ];
            }
        );

        $priorityAlerts = Alert::query()
            ->whereIn('status', [AlertStatus::OPEN->value, AlertStatus::READ->value])
            ->orderByRaw("CASE severity WHEN 'CRITICAL' THEN 0 WHEN 'WARNING' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $hasCustomersTable = Schema::hasTable('customers');
        $hasReceivableCustomerId = Schema::hasColumn('account_receivables', 'customer_id');

        $blockedCustomers = $hasCustomersTable
            ? Customer::query()->where('status', CustomerStatus::BLOCKED)->count()
            : 0;

        if ($hasCustomersTable && $hasReceivableCustomerId) {
            $customersWithOverdue = Customer::query()
                ->select('customers.id', 'customers.name')
                ->selectRaw('SUM(account_receivables.expected_amount) as overdue_total')
                ->join('account_receivables', 'account_receivables.customer_id', '=', 'customers.id')
                ->where('account_receivables.status', ReceivableStatus::OVERDUE)
                ->groupBy('customers.id', 'customers.name')
                ->orderByDesc('overdue_total')
                ->limit(5)
                ->get();

            $topOpenBalanceCustomers = Customer::query()
                ->select('customers.id', 'customers.name')
                ->selectRaw('SUM(account_receivables.expected_amount) as open_total')
                ->join('account_receivables', 'account_receivables.customer_id', '=', 'customers.id')
                ->whereIn('account_receivables.status', [ReceivableStatus::EXPECTED, ReceivableStatus::OVERDUE])
                ->groupBy('customers.id', 'customers.name')
                ->orderByDesc('open_total')
                ->limit(5)
                ->get();
        } else {
            $customersWithOverdue = AccountReceivable::query()
                ->selectRaw('NULL as id, customer as name, SUM(expected_amount) as overdue_total')
                ->where('status', ReceivableStatus::OVERDUE)
                ->groupBy('customer')
                ->orderByDesc('overdue_total')
                ->limit(5)
                ->get();

            $topOpenBalanceCustomers = AccountReceivable::query()
                ->selectRaw('NULL as id, customer as name, SUM(expected_amount) as open_total')
                ->whereIn('status', [ReceivableStatus::EXPECTED, ReceivableStatus::OVERDUE])
                ->groupBy('customer')
                ->orderByDesc('open_total')
                ->limit(5)
                ->get();
        }

        $waitingDownPaymentWorkOrders = WorkOrder::query()
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED])
            ->where('down_payment_amount', '>', 0)
            ->whereColumn('received_amount', '<', 'down_payment_amount')
            ->count();

        $deliveredPendingFinalBalance = WorkOrder::query()
            ->where('status', WorkOrderStatus::DELIVERED)
            ->where('receivable_balance', '>', 0)
            ->count();

        return view('dashboard', [
            'canSeeFinance' => $canSeeFinance,
            'dashboardData' => $dashboardData,
            'priorityAlerts' => $priorityAlerts,
            'blockedCustomers' => $blockedCustomers,
            'customersWithOverdue' => $customersWithOverdue,
            'topOpenBalanceCustomers' => $topOpenBalanceCustomers,
            'waitingDownPaymentWorkOrders' => $waitingDownPaymentWorkOrders,
            'deliveredPendingFinalBalance' => $deliveredPendingFinalBalance,
        ]);
    }
}
