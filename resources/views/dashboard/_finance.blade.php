@php use App\Support\BadgeHelper; @endphp

<x-card title="Financeiro">
    {{-- Mini metrics --}}
    <div class="db-finance-metrics">
        <div class="db-finance-metric">
            <span class="db-finance-metric__label">Recebido no mês</span>
            <strong class="db-finance-metric__value db-finance-metric__value--green">
                R$ {{ number_format($summaryCards['receivedThisMonth'], 0, ',', '.') }}
            </strong>
        </div>
        <div class="db-finance-metric">
            <span class="db-finance-metric__label">Pago no mês</span>
            <strong class="db-finance-metric__value db-finance-metric__value--red">
                R$ {{ number_format($summaryCards['paidThisMonth'], 0, ',', '.') }}
            </strong>
        </div>
        <div class="db-finance-metric">
            <span class="db-finance-metric__label">Saldo projetado</span>
            @php $projected = $summaryCards['projectedCashBalance']; @endphp
            <strong class="db-finance-metric__value {{ $projected < 0 ? 'db-finance-metric__value--red' : ($projected == 0 ? 'db-finance-metric__value--warn' : 'db-finance-metric__value--green') }}">
                R$ {{ number_format($projected, 0, ',', '.') }}
            </strong>
        </div>
        <div class="db-finance-metric">
            <span class="db-finance-metric__label">Inadimplência</span>
            @php $inad = $summaryCards['receivablesOverdueTotal']; @endphp
            <strong class="db-finance-metric__value {{ $inad > 0 ? 'db-finance-metric__value--red' : 'db-finance-metric__value--green' }}">
                R$ {{ number_format($inad, 0, ',', '.') }}
            </strong>
        </div>
        <div class="db-finance-metric">
            <span class="db-finance-metric__label">Entradas (7 dias)</span>
            <strong class="db-finance-metric__value db-finance-metric__value--green">
                {{-- Show receivables due in next 7 days --}}
                @php
                    $in7 = collect($upcomingDueItems)->filter(fn($i) => $i['type'] === 'receivable')->sum('amount');
                @endphp
                R$ {{ number_format($in7, 0, ',', '.') }}
            </strong>
        </div>
        <div class="db-finance-metric">
            <span class="db-finance-metric__label">Saídas (7 dias)</span>
            <strong class="db-finance-metric__value {{ $summaryCards['payablesDueNext7'] > 0 ? 'db-finance-metric__value--red' : '' }}">
                R$ {{ number_format($summaryCards['payablesDueNext7'], 0, ',', '.') }}
            </strong>
        </div>
    </div>

    {{-- Cash flow chart (6 months) --}}
    @if(!empty($cashFlowChart['rows']))
        <div class="db-section-divider"></div>
        <div class="db-chart-header">
            <h4 class="db-chart-title">Entradas vs Saídas — 6 meses</h4>
            <div class="db-chart-legend">
                <span><i class="db-legend-dot db-legend-dot--green"></i>Entradas</span>
                <span><i class="db-legend-dot db-legend-dot--red"></i>Saídas</span>
            </div>
        </div>
        <div class="db-bar-chart">
            @foreach($cashFlowChart['rows'] as $row)
                <div class="db-bar-col">
                    <div class="db-bar-wrap" title="Entradas: R$ {{ number_format($row['income'], 0, ',', '.') }} | Saídas: R$ {{ number_format($row['expense'], 0, ',', '.') }}">
                        <div class="db-bar db-bar--green" style="height:{{ $row['income_pct'] }}%"></div>
                        <div class="db-bar db-bar--red" style="height:{{ $row['expense_pct'] }}%"></div>
                    </div>
                    <span class="db-bar-label">{{ $row['label'] }}</span>
                    @php $net = $row['net']; @endphp
                    <span class="db-bar-net {{ $net >= 0 ? 'db-bar-net--pos' : 'db-bar-net--neg' }}">
                        {{ $net >= 0 ? '+' : '' }}{{ number_format($net, 0, ',', '.') }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Upcoming due items --}}
    @if(!empty($upcomingDueItems))
        <div class="db-section-divider"></div>
        <div class="db-section-header">
            <h4 class="db-section-title">Próximos vencimentos (30 dias)</h4>
        </div>
        <div class="table-wrapper" style="border:none;border-radius:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nome</th>
                        <th>Vencimento</th>
                        <th class="text-right">Valor</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($upcomingDueItems as $due)
                        <tr class="{{ $due['status'] === 'OVERDUE' ? 'db-row-overdue' : '' }}">
                            <td>
                                @if($due['type'] === 'payable')
                                    <span class="db-type-badge db-type-badge--out">Pagar</span>
                                @else
                                    <span class="db-type-badge db-type-badge--in">Receber</span>
                                @endif
                            </td>
                            <td style="font-weight:500;">{{ $due['name'] ?? '—' }}</td>
                            <td class="{{ $due['status'] === 'OVERDUE' ? 'td-danger' : '' }}">
                                {{ $due['date'] ? \Carbon\Carbon::parse($due['date'])->format('d/m/Y') : '—' }}
                            </td>
                            <td class="text-right td-mono">R$ {{ number_format($due['amount'], 2, ',', '.') }}</td>
                            <td><span class="badge {{ $due['status'] === 'OVERDUE' ? 'badge-danger' : 'badge-warning' }}">{{ $due['status_label'] }}</span></td>
                            <td><a href="{{ route($due['route_name'], $due['route_id']) }}" class="btn btn-ghost btn-sm">Abrir</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
        @can('viewAny', \App\Models\AccountReceivable::class)
            <a href="{{ route('receivables.index') }}" class="btn btn-ghost btn-sm">Contas a Receber</a>
        @endcan
        @can('viewAny', \App\Models\AccountPayable::class)
            <a href="{{ route('payables.index') }}" class="btn btn-ghost btn-sm">Contas a Pagar</a>
        @endcan
        @can('report.viewCashFlow')
            <a href="{{ route('reports.cash-flow') }}" class="btn btn-ghost btn-sm">Previsão de Caixa</a>
        @endcan
    </div>
</x-card>
