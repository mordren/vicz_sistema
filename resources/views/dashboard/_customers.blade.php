<x-card title="Clientes">
    {{-- Mini metrics --}}
    <div class="db-ops-metrics">
        <div class="db-ops-metric db-ops-metric--ok">
            <strong>{{ $summaryCards['activeCustomers'] }}</strong><span>Ativos</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['blockedCustomers'] > 0 ? 'db-ops-metric--danger' : '' }}">
            <strong>{{ $summaryCards['blockedCustomers'] }}</strong><span>Bloqueados</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['customersWithOpenBalance'] > 0 ? 'db-ops-metric--info' : '' }}">
            <strong>{{ $summaryCards['customersWithOpenBalance'] }}</strong><span>Com saldo aberto</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['customersWithOverdueCount'] > 0 ? 'db-ops-metric--danger' : '' }}">
            <strong>{{ $summaryCards['customersWithOverdueCount'] }}</strong><span>Com vencidos</span>
        </div>
    </div>

    <div class="db-customers-grid">
        {{-- Top open balance --}}
        @if($canSeeFinance && !empty($customerData['topOpenBalanceCustomers']))
            <div class="db-customer-panel">
                <h4 class="db-section-title">Maior saldo em aberto</h4>
                <ul class="db-customer-list">
                    @foreach($customerData['topOpenBalanceCustomers'] as $c)
                        <li>
                            <a href="{{ route('customers.show', $c['id']) }}">{{ $c['name'] }}</a>
                            <strong class="td-mono">R$ {{ number_format($c['open_total'], 0, ',', '.') }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Overdue customers --}}
        @if($canSeeFinance && !empty($customerData['customersWithOverdue']))
            <div class="db-customer-panel">
                <h4 class="db-section-title">Com recebíveis vencidos</h4>
                <ul class="db-customer-list">
                    @foreach($customerData['customersWithOverdue'] as $c)
                        <li>
                            <a href="{{ route('customers.show', $c['id']) }}">{{ $c['name'] }}</a>
                            <strong class="td-mono" style="color:var(--error);">R$ {{ number_format($c['overdue_total'], 0, ',', '.') }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Recent customers --}}
        @if(!empty($customerData['recentCustomers']))
            <div class="db-customer-panel">
                <h4 class="db-section-title">Cadastros recentes</h4>
                <ul class="db-customer-list">
                    @foreach($customerData['recentCustomers'] as $c)
                        <li>
                            <a href="{{ route('customers.show', $c['id']) }}">{{ $c['name'] }}</a>
                            <span class="td-muted" style="font-size:11px;">{{ $c['created_at'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(empty($customerData['topOpenBalanceCustomers']) && empty($customerData['customersWithOverdue']) && empty($customerData['recentCustomers']))
            <div style="grid-column:span 2;">
                <x-empty-state icon="default" title="Sem dados de clientes" message="Cadastre clientes para ver os indicadores aqui." />
            </div>
        @endif
    </div>

    <div style="margin-top:12px;">
        <a href="{{ route('customers.index') }}" class="btn btn-ghost btn-sm">Ver todos os clientes</a>
    </div>
</x-card>
