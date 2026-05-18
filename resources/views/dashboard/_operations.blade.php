<x-card title="Operacional — Ordens de Serviço">
    {{-- Mini metrics --}}
    <div class="db-ops-metrics">
        <div class="db-ops-metric {{ $summaryCards['overdueWorkOrders'] > 0 ? 'db-ops-metric--danger' : '' }}">
            <strong>{{ $summaryCards['openWorkOrders'] }}</strong><span>Em andamento</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['overdueWorkOrders'] > 0 ? 'db-ops-metric--danger' : 'db-ops-metric--ok' }}">
            <strong>{{ $summaryCards['overdueWorkOrders'] }}</strong><span>Atrasadas</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['nearDeliveryWorkOrders'] > 0 ? 'db-ops-metric--warn' : '' }}">
            <strong>{{ $summaryCards['nearDeliveryWorkOrders'] }}</strong><span>Entrega próxima</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['workOrdersWithoutResponsible'] > 0 ? 'db-ops-metric--warn' : '' }}">
            <strong>{{ $summaryCards['workOrdersWithoutResponsible'] }}</strong><span>Sem responsável</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['waitingDownPaymentWorkOrders'] > 0 ? 'db-ops-metric--info' : '' }}">
            <strong>{{ $summaryCards['waitingDownPaymentWorkOrders'] }}</strong><span>Aguardando entrada</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['deliveredPendingFinalBalance'] > 0 ? 'db-ops-metric--warn' : '' }}">
            <strong>{{ $summaryCards['deliveredPendingFinalBalance'] }}</strong><span>Saldo pendente</span>
        </div>
    </div>

    {{-- Critical WO table --}}
    @if(empty($criticalWorkOrders))
        <x-empty-state icon="check" title="Sem OS críticas" message="Todas as ordens de serviço estão dentro do cronograma." />
    @else
        <div class="db-section-divider"></div>
        <p class="db-section-label" style="margin-bottom:8px;">OS que precisam de atenção</p>
        <div class="table-wrapper" style="border:none;border-radius:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>OS</th>
                        <th>Cliente</th>
                        <th>Responsável</th>
                        <th>Status</th>
                        <th>Entrega</th>
                        <th>Progresso</th>
                        <th class="text-right">Saldo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($criticalWorkOrders as $wo)
                        <tr class="{{ $wo['is_overdue'] ? 'db-row-critical' : ($wo['is_near_delivery'] ? 'db-row-warning' : '') }}">
                            <td>
                                <span style="font-weight:700;font-family:var(--font-brand);">{{ $wo['number'] }}</span>
                                @if($wo['is_overdue'])
                                    <span class="db-badge db-badge--danger">ATRASADA</span>
                                @elseif($wo['is_near_delivery'])
                                    <span class="db-badge db-badge--warn">PROXIMA</span>
                                @elseif($wo['has_no_responsible'])
                                    <span class="db-badge db-badge--neutral">SEM RESP.</span>
                                @elseif($wo['is_waiting_down_pay'])
                                    <span class="db-badge db-badge--info">ENTRADA</span>
                                @endif
                            </td>
                            <td>
                                @if($wo['customer_id'])
                                    <a href="{{ route('customers.show', $wo['customer_id']) }}">{{ $wo['customer'] }}</a>
                                @else
                                    {{ $wo['customer'] }}
                                @endif
                            </td>
                            <td class="{{ !$wo['responsible'] ? 'td-danger' : '' }}">
                                {{ $wo['responsible'] ?? '— Não atribuído —' }}
                            </td>
                            <td><span class="badge badge-neutral" style="font-size:11px;">{{ $wo['status_label'] }}</span></td>
                            <td class="{{ $wo['is_overdue'] ? 'td-danger' : '' }}">
                                @if($wo['delivery_date'])
                                    {{ \Carbon\Carbon::parse($wo['delivery_date'])->format('d/m/Y') }}
                                    @if($wo['delivery_days_left'] !== null)
                                        <span class="td-muted">({{ $wo['delivery_days_left'] >= 0 ? '+' : '' }}{{ $wo['delivery_days_left'] }}d)</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td style="min-width:80px;">
                                <div class="db-progress-track">
                                    <div class="db-progress-fill {{ $wo['is_overdue'] ? 'db-progress-fill--danger' : '' }}" style="width:{{ $wo['progress_percentage'] }}%"></div>
                                </div>
                                <span class="td-muted" style="font-size:11px;">{{ $wo['progress_percentage'] }}%</span>
                            </td>
                            <td class="text-right td-mono {{ $wo['receivable_balance'] > 0 ? 'td-warn' : '' }}">
                                {{ $wo['receivable_balance'] > 0 ? 'R$ '.number_format($wo['receivable_balance'], 0, ',', '.') : '—' }}
                            </td>
                            <td>
                                <a href="{{ route('work-orders.show', $wo['id']) }}" class="btn btn-ghost btn-sm">Abrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div style="margin-top:12px;">
        <a href="{{ route('work-orders.index') }}" class="btn btn-ghost btn-sm">Ver todas as OS</a>
    </div>
</x-card>
