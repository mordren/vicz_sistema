@extends('layouts.app')

@section('title', 'Dashboard — VICZ Implementos')

@section('content')
@php
    use App\Models\AccountPayable;
    use App\Models\AccountReceivable;
    use App\Enums\PayableCategory;
    use App\Enums\PayableStatus;
    use App\Enums\ReceivableStatus;
    use App\Support\BadgeHelper;

    $canSeeFinance = auth()->user()->can('viewAny', \App\Models\AccountReceivable::class)
        && auth()->user()->can('viewAny', \App\Models\AccountPayable::class);

    $dashboardData = \Illuminate\Support\Facades\Cache::remember(
        'dashboard_reports_v2_' . auth()->id() . '_' . ($canSeeFinance ? 'finance' : 'restricted'),
        now()->addSeconds(30),
        function () use ($canSeeFinance) {
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
                    ->orderByRaw("COALESCE(received_date, expected_date) DESC")
                    ->limit(6)
                    ->get();
            }

            $maxChart = max(1, max($receivedByMonth), max($expenseByMonth));
            $totalFlow = $pendingReceivables + $overdueReceivables;
            $predictabilityScore = $totalFlow > 0 ? max(55, min(100, 100 - (($overdueReceivables / $totalFlow) * 100))) : 100;

            $currentMonthKey = now()->format('Y-m');
            $previousMonthKey = now()->copy()->subMonth()->format('Y-m');
            $currentNet = ($receivedByMonth[$currentMonthKey] ?? 0) - ($expenseByMonth[$currentMonthKey] ?? 0);
            $previousNet = ($receivedByMonth[$previousMonthKey] ?? 0) - ($expenseByMonth[$previousMonthKey] ?? 0);
            $variation = $previousNet != 0 ? (($currentNet - $previousNet) / abs($previousNet)) * 100 : 0;

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
@endphp

<x-page-header title="Relatórios e Análises" subtitle="Visão financeira consolidada em {{ now()->format('Y') }}">
    @can('viewAny', \App\Models\AccountReceivable::class)
        <a href="{{ route('receivables.index') }}" class="btn btn-primary">Abrir Contas a Receber</a>
    @endcan
</x-page-header>

<div class="report-toolbar mb-24">
    <div class="report-chip">
        <svg viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/></svg>
        Últimos 6 meses
    </div>
    <div class="report-chip report-chip-green">
        <svg viewBox="0 0 24 24"><path d="M3 17h18v2H3zm2.62-1l5.66-5.66 4 4L20.66 9 22 10.34l-6.72 6.72-4-4L6.95 17.4z"/></svg>
        Previsibilidade ativa
    </div>
</div>

@if(!$canSeeFinance)
    <x-error-state title="Visão financeira restrita" message="Seu perfil não possui acesso aos indicadores financeiros completos deste painel." />
@else
    <div class="report-bento">
        <section class="report-card score-card">
            <p class="report-label">Previsibilidade Financeira</p>
            <h2 class="report-score">{{ number_format($dashboardData['predictabilityScore'], 1, ',', '.') }}%</h2>

            <div class="report-progress-track">
                <div class="report-progress-fill" style="width: {{ $dashboardData['predictabilityScore'] }}%;"></div>
            </div>

            <p class="report-trend {{ $dashboardData['variation'] >= 0 ? 'positive' : 'negative' }}">
                <svg viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>
                {{ $dashboardData['variation'] >= 0 ? '+' : '' }}{{ number_format($dashboardData['variation'], 1, ',', '.') }}% vs mês anterior
            </p>

            <div class="report-kpi-grid">
                <div>
                    <span class="kpi-name">Previsto</span>
                    <strong class="kpi-value">R$ {{ number_format($dashboardData['pendingReceivables'], 0, ',', '.') }}</strong>
                </div>
                <div>
                    <span class="kpi-name">Atrasado</span>
                    <strong class="kpi-value danger">R$ {{ number_format($dashboardData['overdueReceivables'], 0, ',', '.') }}</strong>
                </div>
            </div>
        </section>

        <section class="report-card chart-card">
            <div class="report-head">
                <h3>Recebimentos vs Despesas</h3>
                <div class="report-legend">
                    <span><i class="legend-box legend-primary"></i>Recebimentos</span>
                    <span><i class="legend-box legend-secondary"></i>Despesas</span>
                </div>
            </div>

            <div class="bar-chart-wrap">
                @foreach($dashboardData['months'] as $month)
                    @php
                        $key = $month['key'];
                        $income = (float) ($dashboardData['receivedByMonth'][$key] ?? 0);
                        $expense = (float) ($dashboardData['expenseByMonth'][$key] ?? 0);
                        $incomeHeight = max(6, (int) (($income / $dashboardData['maxChart']) * 100));
                        $expenseHeight = max(6, (int) (($expense / $dashboardData['maxChart']) * 100));
                    @endphp
                    <div class="bar-stack">
                        <div class="bar-column" title="Recebimentos: R$ {{ number_format($income, 2, ',', '.') }} | Despesas: R$ {{ number_format($expense, 2, ',', '.') }}">
                            <div class="bar bar-primary" style="height: {{ $incomeHeight }}%;"></div>
                            <div class="bar bar-secondary" style="height: {{ $expenseHeight }}%;"></div>
                        </div>
                        <span class="bar-label">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="report-card categories-card">
            <div class="report-head">
                <h3>Despesas por Categoria</h3>
            </div>

            @php
                $maxCategoryTotal = max(1, (float) ($dashboardData['expenseCategories']->max('total') ?? 1));
            @endphp

            <div class="categories-list">
                @forelse($dashboardData['expenseCategories'] as $categoryRow)
                    @php
                        $categoryValue = $categoryRow->category;
                        $cat = $categoryValue instanceof PayableCategory
                            ? $categoryValue
                            : PayableCategory::tryFrom((string) $categoryValue);
                        $catLabel = $cat?->label() ?? (is_object($categoryValue) && property_exists($categoryValue, 'value') ? $categoryValue->value : (string) $categoryValue);
                        $catTotal = (float) $categoryRow->total;
                        $pct = max(5, (int) (($catTotal / $maxCategoryTotal) * 100));
                    @endphp
                    <div class="category-item">
                        <div class="category-top">
                            <span>{{ $catLabel }}</span>
                            <strong>R$ {{ number_format($catTotal, 2, ',', '.') }}</strong>
                        </div>
                        <div class="category-track">
                            <div class="category-fill" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @empty
                    <x-empty-state icon="report" title="Sem despesas mapeadas" message="Ainda não há categorias para exibir neste período." />
                @endforelse
            </div>
        </section>

        <section class="report-card history-card">
            <div class="report-head">
                <h3>Histórico de Recebimentos</h3>
                <a href="{{ route('receivables.index') }}" class="report-link">Ver tudo</a>
            </div>

            @if($dashboardData['recentReceivables']->isEmpty())
                <x-empty-state icon="default" title="Sem recebíveis recentes" message="Os lançamentos mais recentes aparecerão aqui." />
            @else
                <div class="table-wrapper" style="border:none;border-radius:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th class="text-right">Valor</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dashboardData['recentReceivables'] as $receivable)
                                <tr>
                                    <td style="font-weight:600;">{{ $receivable->customer }}</td>
                                    <td>{{ ($receivable->received_date ?? $receivable->expected_date)?->format('d/m/Y') }}</td>
                                    <td class="text-right td-mono">R$ {{ number_format((float) ($receivable->received_amount ?? $receivable->expected_amount), 2, ',', '.') }}</td>
                                    <td class="text-center">
                                        <x-status-badge
                                            :variant="BadgeHelper::variant($receivable->status)"
                                            :label="BadgeHelper::label($receivable->status)"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endif

@endsection

@push('styles')
<style>
.report-toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.report-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border: 1px solid var(--outline-variant);
    background: var(--surface);
    color: var(--on-surface-variant);
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 500;
}

.report-chip svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
}

.report-chip-green {
    background: var(--secondary-container);
    border-color: #6ac58e;
    color: var(--on-secondary-container);
}

.report-bento {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 16px;
}

.report-card {
    background: var(--surface);
    border: 1px solid var(--outline-variant);
    border-radius: var(--radius-md);
    padding: 20px;
}

.score-card { grid-column: span 4; }
.chart-card { grid-column: span 8; }
.categories-card { grid-column: span 5; }
.history-card { grid-column: span 7; padding: 0; overflow: hidden; }

.report-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--on-surface-variant);
    margin-bottom: 6px;
}

.report-score {
    font-family: var(--font-brand);
    font-size: 40px;
    line-height: 1.05;
    color: var(--secondary);
}

.report-progress-track {
    width: 100%;
    height: 8px;
    border-radius: var(--radius-full);
    background: var(--surface-container);
    margin: 16px 0 10px;
}

.report-progress-fill {
    height: 100%;
    border-radius: var(--radius-full);
    background: linear-gradient(90deg, var(--secondary), #2f9967);
}

.report-trend {
    display: inline-flex;
    gap: 6px;
    align-items: center;
    font-size: 13px;
    font-weight: 500;
}

.report-trend svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
}

.report-trend.positive { color: var(--status-success); }
.report-trend.negative { color: var(--error); }

.report-kpi-grid {
    margin-top: 16px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.kpi-name {
    display: block;
    font-size: 11px;
    color: var(--on-surface-variant);
    text-transform: uppercase;
    letter-spacing: .05em;
}

.kpi-value {
    display: block;
    margin-top: 2px;
    font-size: 14px;
    font-weight: 600;
    color: var(--primary);
}

.kpi-value.danger { color: var(--error); }

.report-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    gap: 12px;
}

.report-head h3 {
    font-family: var(--font-brand);
    font-size: 18px;
    color: var(--deep-navy);
}

.report-legend {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: var(--on-surface-variant);
}

.legend-box {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 2px;
    margin-right: 4px;
}

.legend-primary { background: var(--primary); }
.legend-secondary { background: var(--secondary-container); }

.bar-chart-wrap {
    height: 230px;
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 10px;
}

.bar-stack {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.bar-column {
    width: 100%;
    max-width: 28px;
    height: 185px;
    display: flex;
    flex-direction: column;
    justify-content: end;
    gap: 2px;
}

.bar {
    width: 100%;
    transition: height .3s ease;
}

.bar-primary {
    background: var(--primary);
    border-radius: 4px 4px 0 0;
}

.bar-secondary {
    background: var(--secondary-container);
    border-radius: 0 0 4px 4px;
}

.bar-label {
    font-size: 11px;
    color: var(--on-surface-variant);
    font-weight: 600;
}

.categories-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.category-item {
    border: 1px solid var(--outline-variant);
    border-radius: var(--radius-sm);
    padding: 10px 12px;
}

.category-top {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 10px;
    margin-bottom: 6px;
}

.category-top span {
    font-size: 13px;
    font-weight: 600;
    color: var(--on-surface);
}

.category-top strong {
    font-size: 13px;
    color: var(--deep-navy);
    font-feature-settings: "tnum";
}

.category-track {
    width: 100%;
    height: 6px;
    border-radius: var(--radius-full);
    background: var(--surface-container);
}

.category-fill {
    height: 100%;
    border-radius: var(--radius-full);
    background: linear-gradient(90deg, var(--primary), var(--secondary));
}

.report-link {
    color: var(--secondary);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

@media (max-width: 1100px) {
    .score-card,
    .chart-card,
    .categories-card,
    .history-card {
        grid-column: span 12;
    }
}

@media (max-width: 640px) {
    .report-score { font-size: 34px; }
    .report-head {
        flex-direction: column;
        align-items: flex-start;
    }
    .bar-chart-wrap {
        height: 200px;
        gap: 6px;
    }
    .bar-column {
        max-width: 22px;
        height: 160px;
    }
}
</style>
@endpush

