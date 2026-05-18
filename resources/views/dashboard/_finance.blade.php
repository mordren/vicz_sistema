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
        <div style="position:relative;height:160px;">
            <canvas id="cashflow-chart-canvas"></canvas>
        </div>
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
        (function () {
            var rows = @json($cashFlowChart['rows']);
            var labels  = rows.map(function(r){ return r.label; });
            var income  = rows.map(function(r){ return r.income; });
            var expense = rows.map(function(r){ return r.expense; });

            var ctx = document.getElementById('cashflow-chart-canvas').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Entradas',
                            data: income,
                            backgroundColor: '#006d41',
                            borderRadius: 4,
                            borderSkipped: false,
                        },
                        {
                            label: 'Saídas',
                            data: expense,
                            backgroundColor: '#b00020',
                            borderRadius: 4,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.dataset.label + ': R$ ' +
                                        ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 0 });
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 }, color: '#6b7080' }
                        },
                        y: {
                            grid: { color: '#e8eaed' },
                            ticks: {
                                font: { size: 11 },
                                color: '#6b7080',
                                callback: function(v) {
                                    return 'R$ ' + (v / 1000).toFixed(0) + 'k';
                                }
                            }
                        }
                    }
                }
            });
        })();
        </script>
        @endpush
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
