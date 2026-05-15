@extends('layouts.app')

@section('title', 'Relatorio de Fluxo de Caixa - VICZ Implementos')

@section('content')
    <x-page-header title="Previsão de Fluxo de Caixa" subtitle="Saldo projetado diário com cenários de risco" />

    <div class="stat-grid" style="grid-template-columns:repeat(4,minmax(0,1fr));">
        <x-stat-card
            label="Saldo inicial"
            value="R$ {{ number_format((float) $forecast['currentBalance'], 2, ',', '.') }}"
            delta="caixa + bancos"
            trend="neutral"
            icon='<path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 16H5V8h14z"/>'
            iconVariant="navy"
        />

        <x-stat-card
            label="Entradas previstas"
            value="R$ {{ number_format((float) $forecast['totals']['expectedInflows'], 2, ',', '.') }}"
            delta="confirmadas"
            trend="positive"
            icon='<path d="M12 2v10.17l3.59-3.58L17 10l-5 5-5-5 1.41-1.41L11 12.17V2zM5 18h14v2H5z"/>'
            iconVariant="green"
        />

        <x-stat-card
            label="Saídas previstas"
            value="R$ {{ number_format((float) $forecast['totals']['expectedOutflows'], 2, ',', '.') }}"
            delta="obrigações abertas"
            trend="negative"
            icon='<path d="M12 22V11.83l-3.59 3.58L7 14l5-5 5 5-1.41 1.41L13 11.83V22zM5 4h14v2H5z"/>'
            iconVariant="red"
        />

        <x-stat-card
            label="Impacto de cenário"
            value="R$ {{ number_format((float) $forecast['totals']['weightedProposalImpact'], 2, ',', '.') }}"
            :delta="$forecast['includeWeightedProposals'] ? 'cenário ativado' : 'cenário desativado'"
            :trend="$forecast['includeWeightedProposals'] ? 'positive' : 'neutral'"
            icon='<path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/>'
            iconVariant="amber"
        />
    </div>

    <x-filter-bar label="Parâmetros da previsão">
        <form id="cash-forecast-filter" method="GET" action="{{ route('reports.cash-flow') }}" class="cash-forecast-filter-grid" style="display:grid;grid-template-columns:220px 220px auto auto;gap:8px;width:100%;align-items:end;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="period">Período</label>
                <select id="period" name="period" class="form-control form-select">
                    @foreach($periods as $period)
                        <option value="{{ $period }}" @selected($filters['period'] === $period)>{{ $period }} dias</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="include_weighted_proposals">Cenário propostas</label>
                <select id="include_weighted_proposals" name="include_weighted_proposals" class="form-control form-select">
                    <option value="0" @selected(!$filters['include_weighted_proposals'])>Excluído</option>
                    <option value="1" @selected($filters['include_weighted_proposals'])>Incluído (ponderado)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Recalcular</button>
            <a href="{{ route('reports.cash-flow') }}" class="btn btn-ghost">Padrão</a>
        </form>
    </x-filter-bar>

    <x-card title="Saldo Atual de Caixa/Bancos">
        <form method="POST" action="{{ route('reports.cash-flow.update-balance') }}" class="cash-balance-form" style="display:grid;grid-template-columns:240px auto;gap:12px;align-items:end;">
            @csrf
            @method('PATCH')
            <input type="hidden" name="period" value="{{ $filters['period'] }}">
            <input type="hidden" name="include_weighted_proposals" value="{{ $filters['include_weighted_proposals'] ? '1' : '0' }}">

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="current_balance">Saldo atual (R$)</label>
                <input id="current_balance" name="current_balance" type="number" step="0.01" class="form-control @error('current_balance') is-invalid @enderror" value="{{ old('current_balance', number_format((float) $forecast['currentBalance'], 2, '.', '')) }}" required>
                @error('current_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-secondary">Atualizar saldo base</button>
        </form>
    </x-card>

    <x-card title="Previsão Diária ({{ $forecast['period'] }} dias)">
        <div id="cash-forecast-loading" style="display:none;margin-bottom:16px;">
            <x-loading-state :rows="5" message="Calculando fluxo projetado..." />
        </div>

        @php
            $hasAnyMovement = $forecast['totals']['expectedInflows'] > 0
                || $forecast['totals']['expectedOutflows'] > 0
                || $forecast['totals']['overdueInflows'] > 0
                || $forecast['totals']['overdueOutflows'] > 0;
        @endphp

        @if(!$hasAnyMovement)
            <x-empty-state
                icon="report"
                title="Sem movimentação prevista"
                message="Não há entradas ou saídas previstas para o período selecionado."
            />
        @else
            <div class="table-wrapper" style="border:none;border-radius:0;">
                <table class="data-table cash-forecast-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th class="text-right">Saldo abertura</th>
                            <th class="text-right">Entradas previstas</th>
                            <th class="text-right">Saídas previstas</th>
                            <th class="text-right">Saldo projetado</th>
                            <th class="text-right">Entradas vencidas</th>
                            <th class="text-right">Saídas vencidas</th>
                            <th class="text-right">Impacto propostas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($forecast['rows'] as $row)
                            <tr class="{{ $row['is_negative'] ? 'cash-row-negative' : '' }} {{ $row['has_high_bill_concentration'] ? 'cash-row-concentration' : '' }}">
                                <td>
                                    <div>{{ $row['date']->format('d/m/Y') }}</div>
                                    @if($row['is_negative'])
                                        <div class="td-muted" style="color:var(--error);">Saldo negativo</div>
                                    @elseif($row['has_high_bill_concentration'])
                                        <div class="td-muted" style="color:#856404;">Alta concentração de contas</div>
                                    @endif
                                </td>
                                <td class="text-right td-mono">R$ {{ number_format((float) $row['opening_balance'], 2, ',', '.') }}</td>
                                <td class="text-right td-mono">R$ {{ number_format((float) $row['expected_inflows'], 2, ',', '.') }}</td>
                                <td class="text-right td-mono">R$ {{ number_format((float) $row['expected_outflows'], 2, ',', '.') }}</td>
                                <td class="text-right td-mono {{ $row['projected_balance'] < 0 ? 'cash-value-negative' : '' }}">R$ {{ number_format((float) $row['projected_balance'], 2, ',', '.') }}</td>
                                <td class="text-right td-mono">R$ {{ number_format((float) $row['overdue_inflows'], 2, ',', '.') }}</td>
                                <td class="text-right td-mono">R$ {{ number_format((float) $row['overdue_outflows'], 2, ',', '.') }}</td>
                                <td class="text-right td-mono {{ $filters['include_weighted_proposals'] ? 'cash-scenario-on' : 'cash-scenario-off' }}">R$ {{ number_format((float) $row['weighted_proposal_impact'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="form-hint" style="margin-top:10px;">
            O saldo projetado considera apenas inflows/outflows confirmados. O cenário de propostas é exibido separadamente.
        </div>
    </x-card>

    <x-card title="Dados para Dashboard (Chart-ready)">
        <div class="text-muted" style="margin-bottom:8px;">
            JSON pronto para consumo no dashboard e gráficos de tendência.
        </div>
        <pre class="cash-chart-json">{{ json_encode($forecast['chart'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        <script id="cash-forecast-chart-data" type="application/json">{!! json_encode($forecast['chart'], JSON_UNESCAPED_UNICODE) !!}</script>
    </x-card>
@endsection

@push('styles')
<style>
.cash-row-negative td {
    background: var(--error-container) !important;
}

.cash-row-concentration td {
    box-shadow: inset 4px 0 0 #ffc107;
}

.cash-value-negative {
    color: var(--error);
    font-weight: 700;
}

.cash-scenario-on {
    color: var(--status-success);
}

.cash-scenario-off {
    color: var(--on-surface-variant);
    opacity: 0.75;
}

.cash-chart-json {
    margin-top: 8px;
    padding: 12px;
    border: 1px solid var(--outline-variant);
    border-radius: var(--radius-sm);
    background: var(--surface-muted);
    font-size: 12px;
    line-height: 1.4;
    max-height: 280px;
    overflow: auto;
}

@media (max-width: 1080px) {
    .cash-forecast-filter-grid {
        grid-template-columns: 1fr 1fr !important;
    }

    .cash-balance-form {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 640px) {
    .cash-forecast-filter-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var filterForm = document.getElementById('cash-forecast-filter');
    var loading = document.getElementById('cash-forecast-loading');

    if (!filterForm || !loading) {
        return;
    }

    filterForm.addEventListener('submit', function () {
        loading.style.display = 'block';
    });
})();
</script>
@endpush
