<x-card title="Comercial — Propostas">
    {{-- Mini metrics --}}
    <div class="db-ops-metrics">
        <div class="db-ops-metric db-ops-metric--info">
            <strong>{{ $summaryCards['openProposals'] }}</strong><span>Enviadas</span>
        </div>
        <div class="db-ops-metric db-ops-metric--info">
            <strong>{{ $summaryCards['negotiatingProposals'] }}</strong><span>Negociação</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['approvedThisMonth'] > 0 ? 'db-ops-metric--ok' : '' }}">
            <strong>{{ $summaryCards['approvedThisMonth'] }}</strong><span>Aprovadas/mês</span>
        </div>
        <div class="db-ops-metric {{ $summaryCards['lostThisMonth'] > 0 ? 'db-ops-metric--danger' : '' }}">
            <strong>{{ $summaryCards['lostThisMonth'] }}</strong><span>Perdidas/mês</span>
        </div>
        <div class="db-ops-metric db-ops-metric--info">
            <strong style="font-size:14px;">R$ {{ number_format($summaryCards['weightedForecast'], 0, ',', '.') }}</strong>
            <span>Forecast pond.</span>
        </div>
    </div>

    {{-- Mini funnel --}}
    @if(!empty($salesFunnel))
        @php $maxFunnelCount = max(1, max(collect($salesFunnel)->pluck('count')->all())); @endphp
        <div class="db-section-divider"></div>
        <p class="db-section-label" style="margin-bottom:8px;">Funil de vendas</p>
        <div class="db-funnel">
            @foreach($salesFunnel as $stage)
                @php
                    $pct = max(10, (int)(($stage['count'] / $maxFunnelCount) * 100));
                    $color = match($stage['status']) {
                        'SENT'        => '#3b82f6',
                        'NEGOTIATING' => '#f59e0b',
                        'APPROVED'    => '#22c55e',
                        'LOST'        => '#ef4444',
                        default       => '#9ca3af',
                    };
                @endphp
                <div class="db-funnel-stage">
                    <span class="db-funnel-label">{{ $stage['status_label'] }}</span>
                    <div class="db-funnel-bar-wrap">
                        <div class="db-funnel-bar" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                        <span class="db-funnel-count">{{ $stage['count'] }}</span>
                        @if($stage['total'] > 0)
                            <span class="db-funnel-amount">R$ {{ number_format($stage['total'], 0, ',', '.') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Proposals follow-up table --}}
    @if(!empty($proposalFollowUps))
        <div class="db-section-divider"></div>
        <p class="db-section-label" style="margin-bottom:8px;">Propostas para follow-up</p>
        <div class="table-wrapper" style="border:none;border-radius:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nº</th>
                        <th>Cliente</th>
                        <th class="text-right">Valor</th>
                        <th class="text-center">%</th>
                        <th>Fechamento</th>
                        <th>Responsável</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proposalFollowUps as $p)
                        <tr class="{{ $p['is_overdue'] ? 'db-row-warning' : '' }}">
                            <td style="font-weight:700;font-family:var(--font-brand);">{{ $p['number'] }}</td>
                            <td>
                                @if($p['customer_id'])
                                    <a href="{{ route('customers.show', $p['customer_id']) }}">{{ $p['customer'] }}</a>
                                @else
                                    {{ $p['customer'] }}
                                @endif
                            </td>
                            <td class="text-right td-mono">R$ {{ number_format($p['amount'], 0, ',', '.') }}</td>
                            <td class="text-center">
                                <span class="db-prob-badge" style="--prob:{{ $p['close_probability'] }}%;">
                                    {{ $p['close_probability'] }}%
                                </span>
                            </td>
                            <td class="{{ $p['is_overdue'] ? 'td-danger' : '' }}">
                                @if($p['expected_close_date'])
                                    {{ \Carbon\Carbon::parse($p['expected_close_date'])->format('d/m/Y') }}
                                    @if($p['days_to_close'] !== null)
                                        <span class="td-muted">({{ $p['days_to_close'] >= 0 ? '+' : '' }}{{ $p['days_to_close'] }}d)</span>
                                    @endif
                                @else
                                    <span class="td-muted">Sem data</span>
                                @endif
                            </td>
                            <td>{{ $p['sales_owner'] ?? '—' }}</td>
                            <td><span class="badge {{ $p['status'] === 'NEGOTIATING' ? 'badge-warning' : 'badge-info' }}">{{ $p['status_label'] }}</span></td>
                            <td><a href="{{ route('proposals.show', $p['id']) }}" class="btn btn-ghost btn-sm">Abrir</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="report" title="Sem propostas ativas" message="Nenhuma proposta em aberto no momento." />
    @endif

    <div style="margin-top:12px;">
        <a href="{{ route('proposals.index') }}" class="btn btn-ghost btn-sm">Ver todas as propostas</a>
    </div>
</x-card>
