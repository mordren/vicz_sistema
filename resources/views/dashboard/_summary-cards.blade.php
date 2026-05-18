<div class="db-kpi-grid">
    {{-- A Receber em Aberto --}}
    @if($canSeeFinance)
        <div class="db-kpi-card {{ $summaryCards['receivablesOverdueTotal'] > 0 ? 'db-kpi-card--alert' : 'db-kpi-card--positive' }}">
            <div class="db-kpi-card__icon">
                <svg viewBox="0 0 24 24"><path d="M12 2v10.17l3.59-3.58L17 10l-5 5-5-5 1.41-1.41L11 12.17V2zM5 18h14v2H5z"/></svg>
            </div>
            <div class="db-kpi-card__body">
                <span class="db-kpi-card__label">A Receber em Aberto</span>
                <strong class="db-kpi-card__value">
                    R$ {{ number_format($summaryCards['receivablesOpenTotal'] + $summaryCards['receivablesOverdueTotal'], 0, ',', '.') }}
                </strong>
                @if($summaryCards['receivablesOverdueTotal'] > 0)
                    <span class="db-kpi-card__sub db-kpi-card__sub--danger">
                        R$ {{ number_format($summaryCards['receivablesOverdueTotal'], 0, ',', '.') }} vencidos
                    </span>
                @else
                    <span class="db-kpi-card__sub">Sem valores vencidos</span>
                @endif
            </div>
        </div>

        {{-- Contas Vencidas --}}
        <div class="db-kpi-card {{ $summaryCards['payablesOverdueTotal'] > 0 ? 'db-kpi-card--danger' : 'db-kpi-card--ok' }}">
            <div class="db-kpi-card__icon">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </div>
            <div class="db-kpi-card__body">
                <span class="db-kpi-card__label">Contas Vencidas</span>
                @if($summaryCards['payablesOverdueTotal'] > 0)
                    <strong class="db-kpi-card__value db-kpi-card__value--danger">
                        R$ {{ number_format($summaryCards['payablesOverdueTotal'], 0, ',', '.') }}
                    </strong>
                    <span class="db-kpi-card__sub db-kpi-card__sub--danger">Pagamento urgente</span>
                @else
                    <strong class="db-kpi-card__value">R$ 0</strong>
                    <span class="db-kpi-card__sub">Sem vencidos</span>
                @endif
            </div>
        </div>

        {{-- A Pagar 7 dias --}}
        <div class="db-kpi-card {{ $summaryCards['payablesDueNext7'] > 0 ? 'db-kpi-card--warning' : 'db-kpi-card--ok' }}">
            <div class="db-kpi-card__icon">
                <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            </div>
            <div class="db-kpi-card__body">
                <span class="db-kpi-card__label">A Pagar (7 dias)</span>
                <strong class="db-kpi-card__value">
                    R$ {{ number_format($summaryCards['payablesDueNext7'], 0, ',', '.') }}
                </strong>
                <span class="db-kpi-card__sub">Previsão de saída</span>
            </div>
        </div>
    @endif

    {{-- OS em Andamento --}}
    <div class="db-kpi-card {{ $summaryCards['overdueWorkOrders'] > 0 ? 'db-kpi-card--danger' : ($summaryCards['openWorkOrders'] > 0 ? 'db-kpi-card--info' : 'db-kpi-card--ok') }}">
        <div class="db-kpi-card__icon">
            <svg viewBox="0 0 24 24"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/></svg>
        </div>
        <div class="db-kpi-card__body">
            <span class="db-kpi-card__label">OS em Andamento</span>
            @if($summaryCards['openWorkOrders'] > 0)
                <strong class="db-kpi-card__value">{{ $summaryCards['openWorkOrders'] }}</strong>
                <span class="db-kpi-card__sub">
                    @if($summaryCards['overdueWorkOrders'] > 0)
                        <span class="db-kpi-card__sub--danger">{{ $summaryCards['overdueWorkOrders'] }} atrasadas</span>
                        @if($summaryCards['nearDeliveryWorkOrders'] > 0) · @endif
                    @endif
                    @if($summaryCards['nearDeliveryWorkOrders'] > 0)
                        {{ $summaryCards['nearDeliveryWorkOrders'] }} entrega próxima
                    @endif
                    @if($summaryCards['overdueWorkOrders'] == 0 && $summaryCards['nearDeliveryWorkOrders'] == 0)
                        Dentro do prazo
                    @endif
                </span>
            @else
                <strong class="db-kpi-card__value">0</strong>
                <span class="db-kpi-card__sub">Nenhuma OS ativa</span>
            @endif
        </div>
    </div>

    {{-- Propostas Abertas --}}
    @php $totalOpenProposals = $summaryCards['openProposals'] + $summaryCards['negotiatingProposals']; @endphp
    <div class="db-kpi-card {{ $totalOpenProposals > 0 ? 'db-kpi-card--info' : 'db-kpi-card--ok' }}">
        <div class="db-kpi-card__icon">
            <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
        </div>
        <div class="db-kpi-card__body">
            <span class="db-kpi-card__label">Propostas Abertas</span>
            <strong class="db-kpi-card__value">{{ $totalOpenProposals }}</strong>
            @if($totalOpenProposals > 0)
                <span class="db-kpi-card__sub">
                    Forecast: R$ {{ number_format($summaryCards['weightedForecast'], 0, ',', '.') }}
                </span>
            @else
                <span class="db-kpi-card__sub">Sem propostas ativas</span>
            @endif
        </div>
    </div>

    {{-- Margem Operacional --}}
    @php $margin = $summaryCards['estimatedOperationalMargin']; @endphp
    <div class="db-kpi-card {{ $margin < 0 ? 'db-kpi-card--danger' : ($margin > 0 ? 'db-kpi-card--positive' : 'db-kpi-card--info') }}">
        <div class="db-kpi-card__icon">
            <svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
        </div>
        <div class="db-kpi-card__body">
            <span class="db-kpi-card__label">Margem Operacional</span>
            @if($summaryCards['openWorkOrders'] > 0)
                <strong class="db-kpi-card__value {{ $margin < 0 ? 'db-kpi-card__value--danger' : '' }}">
                    R$ {{ number_format($margin, 0, ',', '.') }}
                </strong>
                <span class="db-kpi-card__sub">Baseado nas OS ativas</span>
            @else
                <strong class="db-kpi-card__value">—</strong>
                <span class="db-kpi-card__sub">Sem OS ativas</span>
            @endif
        </div>
    </div>
</div>
