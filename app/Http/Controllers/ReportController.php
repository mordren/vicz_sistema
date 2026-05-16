<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $service) {}

    public function financial(Request $request): View
    {
        Gate::authorize('report.viewFinancial');

        return $this->renderScope('financial', $request);
    }

    public function operational(Request $request): View
    {
        Gate::authorize('report.viewOperational');

        return $this->renderScope('operational', $request);
    }

    public function sales(Request $request): View
    {
        Gate::authorize('report.viewSales');

        return $this->renderScope('sales', $request);
    }

    public function exportFinancial(Request $request): Response
    {
        Gate::authorize('report.viewFinancial');

        return $this->exportScope('financial', $request);
    }

    public function exportOperational(Request $request): Response
    {
        Gate::authorize('report.viewOperational');

        return $this->exportScope('operational', $request);
    }

    public function exportSales(Request $request): Response
    {
        Gate::authorize('report.viewSales');

        return $this->exportScope('sales', $request);
    }

    private function renderScope(string $scope, Request $request): View
    {
        $reportOptions = $this->reportOptions($scope);
        $defaultReport = array_key_first($reportOptions);

        $selectedReport = (string) $request->string('report');
        if (! array_key_exists($selectedReport, $reportOptions)) {
            $selectedReport = (string) $defaultReport;
        }

        $filters = [
            'date_from' => (string) $request->string('date_from'),
            'date_to' => (string) $request->string('date_to'),
            'entity' => (string) $request->string('entity'),
            'status' => (string) $request->string('status'),
        ];

        $payload = $this->service->build($scope, $selectedReport, $filters);

        return view('reports.index', [
            'scope' => $scope,
            'scopeTitle' => $this->scopeTitle($scope),
            'reportOptions' => $reportOptions,
            'selectedReport' => $selectedReport,
            'filters' => [
                'date_from' => $filters['date_from'] ?: now()->subDays(30)->toDateString(),
                'date_to' => $filters['date_to'] ?: now()->toDateString(),
                'entity' => $filters['entity'],
                'status' => $filters['status'],
            ],
            'report' => $payload,
            'exportRoute' => $this->exportRoute($scope),
        ]);
    }

    private function exportScope(string $scope, Request $request): Response
    {
        $reportOptions = $this->reportOptions($scope);
        $defaultReport = array_key_first($reportOptions);

        $selectedReport = (string) $request->string('report');
        if (! array_key_exists($selectedReport, $reportOptions)) {
            $selectedReport = (string) $defaultReport;
        }

        $filters = [
            'date_from' => (string) $request->string('date_from'),
            'date_to' => (string) $request->string('date_to'),
            'entity' => (string) $request->string('entity'),
            'status' => (string) $request->string('status'),
        ];

        $payload = $this->service->build($scope, $selectedReport, $filters);
        $csv = $this->service->toCsv($payload['rows'], $payload['columns']);

        $filename = $scope.'_'.$selectedReport.'_'.now()->format('Ymd_His').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename='.$filename,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function reportOptions(string $scope): array
    {
        return match ($scope) {
            'financial' => [
                'payables_by_period' => 'Contas a pagar por periodo',
                'receivables_by_period' => 'Contas a receber por periodo',
                'forecasted_cash_flow' => 'Fluxo de caixa previsto',
                'actual_cash_flow' => 'Fluxo de caixa realizado',
                'overdue_bills' => 'Contas vencidas',
                'overdue_customers' => 'Clientes em atraso',
                'customer_financial_summary' => 'Resumo financeiro por cliente',
            ],
            'operational' => [
                'work_orders_in_progress' => 'Ordens de servico em andamento',
                'overdue_work_orders' => 'Ordens de servico em atraso',
                'margin_by_work_order' => 'Margem por ordem de servico',
                'costs_by_work_order' => 'Custos por ordem de servico',
                'work_orders_by_payment_status' => 'Ordens por status de pagamento',
                'work_orders_waiting_down_payment' => 'Ordens aguardando entrada',
            ],
            'sales' => [
                'open_proposals' => 'Propostas abertas',
                'won_lost_proposals' => 'Propostas ganhas e perdidas',
            ],
            default => [],
        };
    }

    private function scopeTitle(string $scope): string
    {
        return match ($scope) {
            'financial' => 'Relatorios financeiros',
            'operational' => 'Relatorios operacionais',
            'sales' => 'Relatorios comerciais',
            default => 'Relatorios',
        };
    }

    private function exportRoute(string $scope): string
    {
        return match ($scope) {
            'financial' => 'reports.financial.export',
            'operational' => 'reports.operational.export',
            'sales' => 'reports.sales.export',
            default => 'reports.financial.export',
        };
    }
}
