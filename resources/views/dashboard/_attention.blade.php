@php
    use App\Support\BadgeHelper;
    use App\Enums\AlertSeverity;
    $hasCritical = collect($attentionItems)->contains(fn($i) => $i['severity'] === 'CRITICAL');
    $allEmpty = empty($attentionItems) && $priorityAlerts->isEmpty();
@endphp

<x-card title="Atenção imediata">
    @if($allEmpty)
        <x-empty-state
            icon="check"
            title="Nenhuma pendência crítica"
            message="Todas as contas, OS e propostas estão em dia."
        />
    @else
        @if(!empty($attentionItems))
            <div class="db-attention-grid">
                @foreach($attentionItems as $item)
                    @php $isCritical = $item['severity'] === 'CRITICAL'; @endphp
                    <div class="db-attention-card {{ $isCritical ? 'db-attention-card--critical' : 'db-attention-card--warning' }}">
                        <div class="db-attention-card__icon">
                            @if($isCritical)
                                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                            @else
                                <svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
                            @endif
                        </div>
                        <div class="db-attention-card__body">
                            <p class="db-attention-card__title">{{ $item['title'] }}</p>
                            <p class="db-attention-card__desc">{{ $item['description'] }}</p>
                            @if(!empty($item['amount']))
                                <p class="db-attention-card__amount">R$ {{ number_format($item['amount'], 2, ',', '.') }}</p>
                            @endif
                        </div>
                        <a href="{{ route($item['route_name']) }}" class="db-attention-card__action">
                            {{ $item['action_label'] }}
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        @if($priorityAlerts->isNotEmpty())
            <div style="margin-top: {{ !empty($attentionItems) ? '16px' : '0' }};">
                <p class="db-section-label" style="margin-bottom:8px;">Alertas do sistema</p>
                <div class="table-wrapper" style="border:none;border-radius:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Severidade</th>
                                <th>Título</th>
                                <th>Data</th>
                                <th>Ação</th>
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
                                    <td>{{ $alert->created_at?->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('alerts.show', $alert) }}" class="btn btn-ghost btn-sm">Abrir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</x-card>
