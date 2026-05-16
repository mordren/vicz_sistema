@extends('layouts.app')

@section('title', 'Dashboard — VICZ Implementos')

@section('content')
@php
    use App\Enums\PayableCategory;
    use App\Enums\AlertSeverity;
    use App\Support\BadgeHelper;
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

<x-card title="Alertas prioritarios" class="mb-24">
    @if($priorityAlerts->isEmpty())
        <x-empty-state icon="alert" title="Sem alertas abertos" message="Nenhum alerta aberto no momento." />
    @else
        <div class="table-wrapper" style="border:none;border-radius:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Severidade</th>
                        <th>Titulo</th>
                        <th>Data</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($priorityAlerts as $alert)
                        <tr class="{{ $alert->severity === AlertSeverity::CRITICAL ? 'dash-alert-critical' : '' }}">
                            <td>
                                <x-status-badge :variant="BadgeHelper::variant($alert->severity)" :label="BadgeHelper::label($alert->severity)" />
                            </td>
                            <td>
                                <div style="font-weight:600;">{{ $alert->title }}</div>
                                <div class="td-muted">{{ $alert->message }}</div>
                            </td>
                            <td>{{ $alert->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('alerts.show', $alert) }}" class="btn btn-ghost btn-sm">Abrir alerta</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>

<x-card title="Insights de Clientes e OS" class="mb-24">
    <div class="dashboard-insights-grid">
        <div class="insight-mini-card">
            <p class="insight-label">Clientes com vencidos</p>
            <p class="insight-value">{{ $customersWithOverdue->count() }}</p>
        </div>
        <div class="insight-mini-card">
            <p class="insight-label">Clientes bloqueados</p>
            <p class="insight-value">{{ $blockedCustomers }}</p>
        </div>
        <div class="insight-mini-card">
            <p class="insight-label">OS aguardando entrada</p>
            <p class="insight-value">{{ $waitingDownPaymentWorkOrders }}</p>
        </div>
        <div class="insight-mini-card">
            <p class="insight-label">OS entregues com saldo</p>
            <p class="insight-value">{{ $deliveredPendingFinalBalance }}</p>
        </div>
    </div>

    <div class="dashboard-insights-grid" style="margin-top: 16px;">
        <section class="insight-panel">
            <h3 class="insight-title">Top clientes por saldo em aberto</h3>
            @if($topOpenBalanceCustomers->isEmpty())
                <p class="insight-empty">Sem saldos em aberto no momento.</p>
            @else
                <ul class="insight-list">
                    @foreach($topOpenBalanceCustomers as $customer)
                        <li>
                            <span>
                                @if(!empty($customer->id))
                                    <a href="{{ route('customers.show', $customer->id) }}">{{ $customer->name }}</a>
                                @else
                                    {{ $customer->name }}
                                @endif
                            </span>
                            <strong class="td-mono">R$ {{ number_format((float) $customer->open_total, 2, ',', '.') }}</strong>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="insight-panel">
            <h3 class="insight-title">Clientes com maior atraso</h3>
            @if($customersWithOverdue->isEmpty())
                <p class="insight-empty">Nenhum cliente com recebível em atraso.</p>
            @else
                <ul class="insight-list">
                    @foreach($customersWithOverdue as $customer)
                        <li>
                            <span>
                                @if(!empty($customer->id))
                                    <a href="{{ route('customers.show', $customer->id) }}">{{ $customer->name }}</a>
                                @else
                                    {{ $customer->name }}
                                @endif
                            </span>
                            <strong class="td-mono" style="color: var(--error);">R$ {{ number_format((float) $customer->overdue_total, 2, ',', '.') }}</strong>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-card>

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
                $expenseCategories = collect($dashboardData['expenseCategories'] ?? []);
                $maxCategoryTotal = max(1, (float) ($expenseCategories->isNotEmpty() ? $expenseCategories->max('total') : 1));
            @endphp

            <div class="categories-list">
                @forelse($expenseCategories as $categoryRow)
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

.dash-alert-critical td {
    background: var(--error-container) !important;
}

.dashboard-insights-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.insight-mini-card {
    border: 1px solid var(--outline-variant);
    border-radius: var(--radius-sm);
    background: var(--surface);
    padding: 12px;
}

.insight-label {
    margin: 0;
    font-size: 11px;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--on-surface-variant);
    font-weight: 600;
}

.insight-value {
    margin: 8px 0 0;
    font-size: 24px;
    line-height: 1;
    color: var(--deep-navy);
    font-family: var(--font-brand);
    font-weight: 700;
}

.insight-panel {
    grid-column: span 2;
    border: 1px solid var(--outline-variant);
    border-radius: var(--radius-sm);
    padding: 12px;
}

.insight-title {
    margin: 0 0 10px;
    color: var(--deep-navy);
    font-size: 15px;
    font-family: var(--font-brand);
}

.insight-empty {
    margin: 0;
    color: var(--on-surface-variant);
    font-size: 13px;
}

.insight-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.insight-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding-bottom: 8px;
    border-bottom: 1px dashed var(--outline-variant);
}

.insight-list li:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.insight-list a {
    color: var(--primary);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}

.insight-list a:hover {
    text-decoration: underline;
}

@media (max-width: 1100px) {
    .score-card,
    .chart-card,
    .categories-card,
    .history-card {
        grid-column: span 12;
    }

    .dashboard-insights-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .insight-panel {
        grid-column: span 2;
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

    .dashboard-insights-grid {
        grid-template-columns: 1fr;
    }

    .insight-panel {
        grid-column: span 1;
    }
}
</style>
@endpush

