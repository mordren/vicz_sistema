<?php

namespace App\Services;

use App\Enums\PayableCategory;
use App\Enums\PayableStatus;
use App\Enums\ProposalStatus;
use App\Enums\ReceivableStatus;
use App\Enums\WorkOrderStatus;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Proposal;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;

class ReportService
{
    public function __construct(private readonly CashForecastService $cashForecastService) {}

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function build(string $scope, string $report, array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        return match ($scope) {
            'financial' => $this->buildFinancialReport($report, $filters),
            'operational' => $this->buildOperationalReport($report, $filters),
            'sales' => $this->buildSalesReport($report, $filters),
            default => throw new \InvalidArgumentException('Escopo de relatorio invalido.'),
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $columns
     */
    public function toCsv(array $rows, array $columns): string
    {
        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, array_values($columns), ';');

        foreach ($rows as $row) {
            $line = [];
            foreach (array_keys($columns) as $key) {
                $value = $row[$key] ?? '';
                if (is_float($value)) {
                    $value = number_format($value, 2, '.', '');
                }
                $line[] = $value;
            }
            fputcsv($stream, $line, ';');
        }

        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $csv;
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function buildFinancialReport(string $report, array $filters): array
    {
        return match ($report) {
            'payables_by_period' => $this->reportPayablesByPeriod($filters),
            'receivables_by_period' => $this->reportReceivablesByPeriod($filters),
            'forecasted_cash_flow' => $this->reportForecastedCashFlow($filters),
            'actual_cash_flow' => $this->reportActualCashFlow($filters),
            'overdue_bills' => $this->reportOverdueBills($filters),
            'overdue_customers' => $this->reportOverdueCustomers($filters),
            'customer_financial_summary' => $this->reportCustomerFinancialSummary($filters),
            default => $this->reportPayablesByPeriod($filters),
        };
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function buildOperationalReport(string $report, array $filters): array
    {
        return match ($report) {
            'work_orders_in_progress' => $this->reportWorkOrdersInProgress($filters),
            'overdue_work_orders' => $this->reportOverdueWorkOrders($filters),
            'margin_by_work_order' => $this->reportMarginByWorkOrder($filters),
            'costs_by_work_order' => $this->reportCostsByWorkOrder($filters),
            'work_orders_by_payment_status' => $this->reportWorkOrdersByPaymentStatus($filters),
            'work_orders_waiting_down_payment' => $this->reportWorkOrdersWaitingDownPayment($filters),
            default => $this->reportWorkOrdersInProgress($filters),
        };
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function buildSalesReport(string $report, array $filters): array
    {
        return match ($report) {
            'open_proposals' => $this->reportOpenProposals($filters),
            'won_lost_proposals' => $this->reportWonLostProposals($filters),
            default => $this->reportOpenProposals($filters),
        };
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, string>
     */
    private function normalizeFilters(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30)->toDateString();
        $dateTo = $filters['date_to'] ?? now()->toDateString();

        if ($dateFrom === '') {
            $dateFrom = now()->subDays(30)->toDateString();
        }

        if ($dateTo === '') {
            $dateTo = now()->toDateString();
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'entity' => $filters['entity'] ?? '',
            'status' => $filters['status'] ?? '',
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportPayablesByPeriod(array $filters): array
    {
        $query = AccountPayable::query()
            ->whereBetween('due_date', [$filters['date_from'], $filters['date_to']])
            ->orderBy('due_date')
            ->orderByDesc('id');

        if ($filters['entity'] !== '') {
            $query->where('supplier', 'like', '%'.$filters['entity'].'%');
        }

        if (($status = PayableStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status->value);
        } else {
            $query->where('status', '!=', PayableStatus::CANCELLED->value);
        }

        $rows = $query->get(['id', 'supplier', 'description', 'category', 'due_date', 'status', 'amount'])
            ->map(function (AccountPayable $payable): array {
                $category = $payable->category;
                $categoryLabel = $category instanceof PayableCategory
                    ? $category->label()
                    : (PayableCategory::tryFrom((string) $category)?->label() ?? (string) $category);

                return [
                    'id' => $payable->id,
                    'supplier' => $payable->supplier,
                    'description' => $payable->description,
                    'category' => $categoryLabel,
                    'date' => optional($payable->due_date)->format('d/m/Y'),
                    'status' => $payable->status->label(),
                    'amount' => (float) $payable->amount,
                ];
            })
            ->values()
            ->all();

        return [
            'title' => 'Contas a pagar por periodo',
            'columns' => [
                'supplier' => 'Fornecedor',
                'description' => 'Descricao',
                'category' => 'Categoria',
                'date' => 'Vencimento',
                'status' => 'Status',
                'amount' => 'Valor',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'amount' => array_sum(array_column($rows, 'amount')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportReceivablesByPeriod(array $filters): array
    {
        $query = AccountReceivable::query()
            ->whereBetween('expected_date', [$filters['date_from'], $filters['date_to']])
            ->orderBy('expected_date')
            ->orderByDesc('id');

        if ($filters['entity'] !== '') {
            $query->where('customer', 'like', '%'.$filters['entity'].'%');
        }

        if (($status = ReceivableStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status->value);
        } else {
            $query->where('status', '!=', ReceivableStatus::CANCELLED->value);
        }

        $rows = $query->get(['id', 'customer', 'description', 'expected_date', 'status', 'expected_amount', 'received_amount'])
            ->map(fn (AccountReceivable $receivable) => [
                'customer' => $receivable->customer,
                'description' => $receivable->description,
                'date' => optional($receivable->expected_date)->format('d/m/Y'),
                'status' => $receivable->status->label(),
                'expected_amount' => (float) $receivable->expected_amount,
                'received_amount' => (float) ($receivable->received_amount ?? 0),
            ])
            ->values()
            ->all();

        return [
            'title' => 'Contas a receber por periodo',
            'columns' => [
                'customer' => 'Cliente',
                'description' => 'Descricao',
                'date' => 'Previsto para',
                'status' => 'Status',
                'expected_amount' => 'Valor previsto',
                'received_amount' => 'Valor recebido',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'expected_amount' => array_sum(array_column($rows, 'expected_amount')),
                'received_amount' => array_sum(array_column($rows, 'received_amount')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportForecastedCashFlow(array $filters): array
    {
        $days = max(1, (int) Carbon::parse($filters['date_from'])->diffInDays(Carbon::parse($filters['date_to'])) + 1);
        $period = min(90, $days);

        $forecast = $this->cashForecastService->buildForecast($period, false);

        $rows = collect($forecast['rows'])
            ->map(fn (array $row) => [
                'date' => $row['date']->format('d/m/Y'),
                'opening_balance' => (float) $row['opening_balance'],
                'expected_inflows' => (float) $row['expected_inflows'],
                'expected_outflows' => (float) $row['expected_outflows'],
                'projected_balance' => (float) $row['projected_balance'],
            ])
            ->all();

        return [
            'title' => 'Fluxo de caixa previsto',
            'columns' => [
                'date' => 'Data',
                'opening_balance' => 'Saldo inicial',
                'expected_inflows' => 'Entradas previstas',
                'expected_outflows' => 'Saidas previstas',
                'projected_balance' => 'Saldo projetado',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'expected_inflows' => (float) $forecast['totals']['expectedInflows'],
                'expected_outflows' => (float) $forecast['totals']['expectedOutflows'],
                'ending_balance' => (float) ($rows[count($rows) - 1]['projected_balance'] ?? 0),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportActualCashFlow(array $filters): array
    {
        $inflows = AccountReceivable::query()
            ->where('status', ReceivableStatus::RECEIVED->value)
            ->whereBetween('received_date', [$filters['date_from'], $filters['date_to']])
            ->selectRaw('received_date as flow_date, SUM(received_amount) as total')
            ->groupBy('received_date')
            ->pluck('total', 'flow_date')
            ->all();

        $outflows = AccountPayable::query()
            ->where('status', PayableStatus::PAID->value)
            ->whereBetween('payment_date', [$filters['date_from'], $filters['date_to']])
            ->selectRaw('payment_date as flow_date, SUM(COALESCE(paid_amount, amount)) as total')
            ->groupBy('payment_date')
            ->pluck('total', 'flow_date')
            ->all();

        $dates = collect(array_keys($inflows))
            ->merge(array_keys($outflows))
            ->unique()
            ->sort()
            ->values();

        $running = 0.0;
        $rows = [];

        foreach ($dates as $date) {
            $entry = (float) ($inflows[$date] ?? 0);
            $exit = (float) ($outflows[$date] ?? 0);
            $net = $entry - $exit;
            $running += $net;

            $rows[] = [
                'date' => Carbon::parse($date)->format('d/m/Y'),
                'inflows' => $entry,
                'outflows' => $exit,
                'net' => $net,
                'running_balance' => $running,
            ];
        }

        return [
            'title' => 'Fluxo de caixa realizado',
            'columns' => [
                'date' => 'Data',
                'inflows' => 'Entradas realizadas',
                'outflows' => 'Saidas realizadas',
                'net' => 'Resultado do dia',
                'running_balance' => 'Acumulado',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'inflows' => array_sum(array_column($rows, 'inflows')),
                'outflows' => array_sum(array_column($rows, 'outflows')),
                'net' => array_sum(array_column($rows, 'net')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportOverdueBills(array $filters): array
    {
        $query = AccountPayable::query()
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereIn('status', [PayableStatus::OPEN->value, PayableStatus::OVERDUE->value])
            ->orderBy('due_date');

        if ($filters['entity'] !== '') {
            $query->where('supplier', 'like', '%'.$filters['entity'].'%');
        }

        $rows = $query->get(['supplier', 'description', 'due_date', 'amount', 'status'])
            ->map(fn (AccountPayable $payable) => [
                'supplier' => $payable->supplier,
                'description' => $payable->description,
                'due_date' => optional($payable->due_date)->format('d/m/Y'),
                'status' => $payable->status->label(),
                'amount' => (float) $payable->amount,
            ])
            ->all();

        return [
            'title' => 'Contas vencidas',
            'columns' => [
                'supplier' => 'Fornecedor',
                'description' => 'Descricao',
                'due_date' => 'Vencimento',
                'status' => 'Status',
                'amount' => 'Valor',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'amount' => array_sum(array_column($rows, 'amount')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportOverdueCustomers(array $filters): array
    {
        $query = AccountReceivable::query()
            ->whereDate('expected_date', '<', now()->toDateString())
            ->whereIn('status', [ReceivableStatus::EXPECTED->value, ReceivableStatus::OVERDUE->value])
            ->selectRaw('customer, SUM(expected_amount) as overdue_amount, COUNT(*) as overdue_count')
            ->groupBy('customer')
            ->orderByDesc('overdue_amount');

        if ($filters['entity'] !== '') {
            $query->havingRaw('customer like ?', ['%'.$filters['entity'].'%']);
        }

        $rows = $query->get()
            ->map(fn ($row) => [
                'customer' => $row->customer,
                'overdue_count' => (int) $row->overdue_count,
                'overdue_amount' => (float) $row->overdue_amount,
            ])
            ->all();

        return [
            'title' => 'Clientes em atraso',
            'columns' => [
                'customer' => 'Cliente',
                'overdue_count' => 'Titulos em atraso',
                'overdue_amount' => 'Valor em atraso',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'overdue_amount' => array_sum(array_column($rows, 'overdue_amount')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportCustomerFinancialSummary(array $filters): array
    {
        $query = WorkOrder::query()
            ->whereBetween('opened_at', [$filters['date_from'], $filters['date_to']])
            ->where('status', '!=', WorkOrderStatus::CANCELLED->value)
            ->selectRaw('customer, COUNT(*) as work_orders_count, SUM(contracted_amount) as contracted_total, SUM(received_amount) as received_total, SUM(receivable_balance) as pending_total')
            ->groupBy('customer')
            ->orderByDesc('pending_total');

        if ($filters['entity'] !== '') {
            $query->havingRaw('customer like ?', ['%'.$filters['entity'].'%']);
        }

        $rows = $query->get()
            ->map(fn ($row) => [
                'customer' => (string) $row->customer,
                'work_orders_count' => (int) $row->work_orders_count,
                'contracted_total' => (float) $row->contracted_total,
                'received_total' => (float) $row->received_total,
                'pending_total' => (float) $row->pending_total,
            ])
            ->all();

        return [
            'title' => 'Resumo financeiro por cliente',
            'columns' => [
                'customer' => 'Cliente',
                'work_orders_count' => 'OS',
                'contracted_total' => 'Contratado',
                'received_total' => 'Recebido',
                'pending_total' => 'Saldo pendente',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'work_orders_count' => array_sum(array_column($rows, 'work_orders_count')),
                'contracted_total' => array_sum(array_column($rows, 'contracted_total')),
                'received_total' => array_sum(array_column($rows, 'received_total')),
                'pending_total' => array_sum(array_column($rows, 'pending_total')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportWorkOrdersInProgress(array $filters): array
    {
        $query = WorkOrder::query()
            ->with('responsibleUser:id,name')
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->whereBetween('expected_delivery_date', [$filters['date_from'], $filters['date_to']])
            ->orderBy('expected_delivery_date');

        if ($filters['entity'] !== '') {
            $query->where('customer', 'like', '%'.$filters['entity'].'%');
        }

        if (($status = WorkOrderStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status->value);
        }

        $rows = $query->get(['id', 'number', 'customer', 'status', 'progress_percentage', 'expected_delivery_date', 'responsible_user_id'])
            ->map(fn (WorkOrder $workOrder) => [
                'number' => $workOrder->number,
                'customer' => $workOrder->customer,
                'responsible' => $workOrder->responsibleUser?->name ?? '-',
                'status' => $workOrder->status->label(),
                'progress' => (int) $workOrder->progress_percentage,
                'delivery_date' => optional($workOrder->expected_delivery_date)->format('d/m/Y'),
            ])
            ->all();

        return [
            'title' => 'Ordens em andamento',
            'columns' => [
                'number' => 'OS',
                'customer' => 'Cliente',
                'responsible' => 'Responsavel',
                'status' => 'Status',
                'progress' => 'Progresso (%)',
                'delivery_date' => 'Entrega prevista',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportOverdueWorkOrders(array $filters): array
    {
        $query = WorkOrder::query()
            ->with('responsibleUser:id,name')
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->where(function ($builder): void {
                $builder->whereDate('expected_delivery_date', '<', now()->toDateString())
                    ->orWhereDate('recalculated_delivery_date', '<', now()->toDateString());
            })
            ->orderBy('expected_delivery_date');

        if ($filters['entity'] !== '') {
            $query->where('customer', 'like', '%'.$filters['entity'].'%');
        }

        $rows = $query->get(['id', 'number', 'customer', 'status', 'expected_delivery_date', 'recalculated_delivery_date', 'responsible_user_id'])
            ->map(fn (WorkOrder $workOrder) => [
                'number' => $workOrder->number,
                'customer' => $workOrder->customer,
                'responsible' => $workOrder->responsibleUser?->name ?? '-',
                'status' => $workOrder->status->label(),
                'delivery_date' => optional($workOrder->recalculated_delivery_date ?? $workOrder->expected_delivery_date)?->format('d/m/Y'),
            ])
            ->all();

        return [
            'title' => 'Ordens em atraso',
            'columns' => [
                'number' => 'OS',
                'customer' => 'Cliente',
                'responsible' => 'Responsavel',
                'status' => 'Status',
                'delivery_date' => 'Data limite',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportMarginByWorkOrder(array $filters): array
    {
        $query = WorkOrder::query()
            ->whereBetween('opened_at', [$filters['date_from'], $filters['date_to']])
            ->orderByDesc('actual_margin');

        if ($filters['entity'] !== '') {
            $query->where('customer', 'like', '%'.$filters['entity'].'%');
        }

        if (($status = WorkOrderStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status->value);
        } else {
            $query->where('status', '!=', WorkOrderStatus::CANCELLED->value);
        }

        $rows = $query->get(['number', 'customer', 'status', 'contracted_amount', 'actual_cost', 'actual_margin'])
            ->map(fn (WorkOrder $workOrder) => [
                'number' => $workOrder->number,
                'customer' => $workOrder->customer,
                'status' => $workOrder->status->label(),
                'contracted_amount' => (float) $workOrder->contracted_amount,
                'actual_cost' => (float) $workOrder->actual_cost,
                'actual_margin' => (float) $workOrder->actual_margin,
            ])
            ->all();

        return [
            'title' => 'Margem por ordem de servico',
            'columns' => [
                'number' => 'OS',
                'customer' => 'Cliente',
                'status' => 'Status',
                'contracted_amount' => 'Valor contratado',
                'actual_cost' => 'Custo real',
                'actual_margin' => 'Margem real',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'contracted_amount' => array_sum(array_column($rows, 'contracted_amount')),
                'actual_cost' => array_sum(array_column($rows, 'actual_cost')),
                'actual_margin' => array_sum(array_column($rows, 'actual_margin')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportCostsByWorkOrder(array $filters): array
    {
        $query = WorkOrder::query()
            ->whereBetween('opened_at', [$filters['date_from'], $filters['date_to']])
            ->orderByDesc('actual_cost');

        if ($filters['entity'] !== '') {
            $query->where('customer', 'like', '%'.$filters['entity'].'%');
        }

        if (($status = WorkOrderStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status->value);
        } else {
            $query->where('status', '!=', WorkOrderStatus::CANCELLED->value);
        }

        $rows = $query->get(['number', 'customer', 'status', 'estimated_cost', 'actual_cost'])
            ->map(function (WorkOrder $workOrder): array {
                $estimated = (float) $workOrder->estimated_cost;
                $actual = (float) $workOrder->actual_cost;

                return [
                    'number' => $workOrder->number,
                    'customer' => $workOrder->customer,
                    'status' => $workOrder->status->label(),
                    'estimated_cost' => $estimated,
                    'actual_cost' => $actual,
                    'variance' => $actual - $estimated,
                ];
            })
            ->all();

        return [
            'title' => 'Custos por ordem de servico',
            'columns' => [
                'number' => 'OS',
                'customer' => 'Cliente',
                'status' => 'Status',
                'estimated_cost' => 'Custo estimado',
                'actual_cost' => 'Custo real',
                'variance' => 'Desvio',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'estimated_cost' => array_sum(array_column($rows, 'estimated_cost')),
                'actual_cost' => array_sum(array_column($rows, 'actual_cost')),
                'variance' => array_sum(array_column($rows, 'variance')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportWorkOrdersByPaymentStatus(array $filters): array
    {
        $query = WorkOrder::query()
            ->whereBetween('opened_at', [$filters['date_from'], $filters['date_to']])
            ->where('status', '!=', WorkOrderStatus::CANCELLED->value)
            ->orderByDesc('receivable_balance');

        if ($filters['entity'] !== '') {
            $query->where('customer', 'like', '%'.$filters['entity'].'%');
        }

        if (($status = WorkOrderStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status->value);
        }

        $rows = $query->get(['number', 'customer', 'status', 'contracted_amount', 'received_amount', 'receivable_balance'])
            ->map(function (WorkOrder $workOrder): array {
                $contracted = (float) $workOrder->contracted_amount;
                $received = (float) $workOrder->received_amount;

                $paymentStatus = 'Sem recebimento';
                if ($received > 0 && $received < $contracted) {
                    $paymentStatus = 'Parcial';
                } elseif ($received >= $contracted) {
                    $paymentStatus = 'Liquidado';
                }

                return [
                    'number' => $workOrder->number,
                    'customer' => $workOrder->customer,
                    'work_order_status' => $workOrder->status->label(),
                    'payment_status' => $paymentStatus,
                    'contracted_amount' => $contracted,
                    'received_amount' => $received,
                    'receivable_balance' => (float) $workOrder->receivable_balance,
                ];
            })
            ->all();

        return [
            'title' => 'Ordens por status de pagamento',
            'columns' => [
                'number' => 'OS',
                'customer' => 'Cliente',
                'work_order_status' => 'Status da OS',
                'payment_status' => 'Status financeiro',
                'contracted_amount' => 'Contratado',
                'received_amount' => 'Recebido',
                'receivable_balance' => 'Saldo',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'contracted_amount' => array_sum(array_column($rows, 'contracted_amount')),
                'received_amount' => array_sum(array_column($rows, 'received_amount')),
                'receivable_balance' => array_sum(array_column($rows, 'receivable_balance')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportWorkOrdersWaitingDownPayment(array $filters): array
    {
        $query = WorkOrder::query()
            ->whereBetween('opened_at', [$filters['date_from'], $filters['date_to']])
            ->whereNotIn('status', [WorkOrderStatus::DELIVERED->value, WorkOrderStatus::CANCELLED->value])
            ->where('down_payment_amount', '>', 0)
            ->whereColumn('received_amount', '<', 'down_payment_amount')
            ->orderBy('expected_delivery_date');

        if ($filters['entity'] !== '') {
            $query->where('customer', 'like', '%'.$filters['entity'].'%');
        }

        if (($status = WorkOrderStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status->value);
        }

        $rows = $query->get(['number', 'customer', 'status', 'expected_delivery_date', 'down_payment_amount', 'received_amount'])
            ->map(function (WorkOrder $workOrder): array {
                $pendingEntry = max(0, (float) $workOrder->down_payment_amount - (float) $workOrder->received_amount);

                return [
                    'number' => $workOrder->number,
                    'customer' => $workOrder->customer,
                    'status' => $workOrder->status->label(),
                    'expected_delivery_date' => optional($workOrder->expected_delivery_date)->format('d/m/Y'),
                    'down_payment_amount' => (float) $workOrder->down_payment_amount,
                    'received_amount' => (float) $workOrder->received_amount,
                    'pending_entry' => $pendingEntry,
                ];
            })
            ->all();

        return [
            'title' => 'Ordens aguardando entrada',
            'columns' => [
                'number' => 'OS',
                'customer' => 'Cliente',
                'status' => 'Status',
                'expected_delivery_date' => 'Entrega prevista',
                'down_payment_amount' => 'Entrada prevista',
                'received_amount' => 'Entrada recebida',
                'pending_entry' => 'Entrada pendente',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'down_payment_amount' => array_sum(array_column($rows, 'down_payment_amount')),
                'received_amount' => array_sum(array_column($rows, 'received_amount')),
                'pending_entry' => array_sum(array_column($rows, 'pending_entry')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportOpenProposals(array $filters): array
    {
        $query = Proposal::query()
            ->whereIn('status', [ProposalStatus::SENT->value, ProposalStatus::NEGOTIATING->value])
            ->whereBetween('expected_close_date', [$filters['date_from'], $filters['date_to']])
            ->orderByDesc('weighted_amount');

        if ($filters['entity'] !== '') {
            $query->where('customer', 'like', '%'.$filters['entity'].'%');
        }

        if (($status = ProposalStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status->value);
        }

        $rows = $query->get(['number', 'customer', 'status', 'expected_close_date', 'amount', 'weighted_amount', 'close_probability'])
            ->map(fn (Proposal $proposal) => [
                'number' => $proposal->number,
                'customer' => $proposal->customer,
                'status' => $proposal->status->label(),
                'expected_close_date' => optional($proposal->expected_close_date)->format('d/m/Y'),
                'close_probability' => (int) $proposal->close_probability,
                'amount' => (float) $proposal->amount,
                'weighted_amount' => (float) $proposal->weighted_amount,
            ])
            ->all();

        return [
            'title' => 'Propostas abertas',
            'columns' => [
                'number' => 'Proposta',
                'customer' => 'Cliente',
                'status' => 'Status',
                'expected_close_date' => 'Fechamento previsto',
                'close_probability' => 'Probabilidade (%)',
                'amount' => 'Valor',
                'weighted_amount' => 'Valor ponderado',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'amount' => array_sum(array_column($rows, 'amount')),
                'weighted_amount' => array_sum(array_column($rows, 'weighted_amount')),
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function reportWonLostProposals(array $filters): array
    {
        $query = Proposal::query()
            ->whereIn('status', [ProposalStatus::APPROVED->value, ProposalStatus::LOST->value])
            ->whereBetween('updated_at', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'])
            ->orderByDesc('updated_at');

        if ($filters['entity'] !== '') {
            $query->where('customer', 'like', '%'.$filters['entity'].'%');
        }

        if (($status = ProposalStatus::tryFrom($filters['status'])) !== null) {
            $query->where('status', $status->value);
        }

        $rows = $query->get(['number', 'customer', 'status', 'amount', 'updated_at'])
            ->map(fn (Proposal $proposal) => [
                'number' => $proposal->number,
                'customer' => $proposal->customer,
                'status' => $proposal->status->label(),
                'amount' => (float) $proposal->amount,
                'closed_at' => optional($proposal->updated_at)->format('d/m/Y H:i'),
            ])
            ->all();

        $wonTotal = collect($rows)->where('status', ProposalStatus::APPROVED->label())->sum('amount');
        $lostTotal = collect($rows)->where('status', ProposalStatus::LOST->label())->sum('amount');

        return [
            'title' => 'Propostas ganhas e perdidas',
            'columns' => [
                'number' => 'Proposta',
                'customer' => 'Cliente',
                'status' => 'Resultado',
                'amount' => 'Valor',
                'closed_at' => 'Fechada em',
            ],
            'rows' => $rows,
            'totals' => [
                'count' => count($rows),
                'won_total' => (float) $wonTotal,
                'lost_total' => (float) $lostTotal,
            ],
        ];
    }
}
