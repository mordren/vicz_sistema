@extends('layouts.app')

@section('title', 'Alertas - VICZ Implementos')

@section('content')
    @php
        use App\Enums\AlertSeverity;
        use App\Enums\AlertStatus;
        use App\Enums\AlertType;
        use App\Support\BadgeHelper;
    @endphp

    <x-page-header
        title="Alertas"
        subtitle="Monitoramento automatico de riscos financeiros, operacionais e comerciais"
    >
        @can('create', \App\Models\Alert::class)
            <form method="POST" action="{{ route('alerts.generate') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Atualizar alertas</button>
            </form>
        @endcan
    </x-page-header>

    <div class="stat-grid" style="grid-template-columns:repeat(3,minmax(0,1fr));">
        <x-stat-card
            label="Abertos"
            :value="$summary['open']"
            delta="pendentes"
            trend="neutral"
            icon='<path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 16H5V8h14z"/>'
            iconVariant="navy"
        />

        <x-stat-card
            label="Criticos abertos"
            :value="$summary['criticalOpen']"
            delta="prioridade maxima"
            :trend="$summary['criticalOpen'] > 0 ? 'negative' : 'positive'"
            icon='<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>'
            :iconVariant="$summary['criticalOpen'] > 0 ? 'red' : 'green'"
        />

        <x-stat-card
            label="Resolvidos"
            :value="$summary['resolved']"
            delta="historico"
            trend="positive"
            icon='<path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/>'
            iconVariant="green"
        />
    </div>

    <x-filter-bar label="Filtros">
        <form id="alert-filter-form" method="GET" action="{{ route('alerts.index') }}" class="alerts-filter-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr 2fr auto auto;gap:8px;width:100%;align-items:end;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="type">Tipo</label>
                <select id="type" name="type" class="form-control form-select">
                    <option value="">Todos</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ AlertType::tryFrom($type)?->label() ?? $type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="severity">Severidade</label>
                <select id="severity" name="severity" class="form-control form-select">
                    <option value="">Todas</option>
                    @foreach($severities as $severity)
                        <option value="{{ $severity->value }}" @selected($filters['severity'] === $severity->value)>{{ $severity->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-control form-select">
                    <option value="">Todos</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="date_from">Data (de)</label>
                <input id="date_from" name="date_from" type="date" class="form-control" value="{{ $filters['date_from'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="date_to">Data (ate)</label>
                <input id="date_to" name="date_to" type="date" class="form-control" value="{{ $filters['date_to'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="search">Busca</label>
                <input id="search" name="search" type="text" class="form-control" value="{{ $filters['search'] }}" placeholder="Titulo ou mensagem">
            </div>

            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('alerts.index') }}" class="btn btn-ghost">Limpar</a>
        </form>
    </x-filter-bar>

    <x-card title="Lista de Alertas">
        <div id="alerts-loading" style="display:none;margin-bottom:16px;">
            <x-loading-state :rows="4" message="Aplicando filtros..." />
        </div>

        @if($alerts->isEmpty())
            <x-empty-state
                icon="alert"
                title="Nenhum alerta encontrado"
                message="Nao ha alertas para os filtros aplicados."
            />
        @else
            <div class="table-wrapper" style="border:none;border-radius:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Severidade</th>
                            <th>Titulo</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Registro</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                            <tr class="{{ $alert->status === AlertStatus::RESOLVED ? 'alert-row-resolved' : '' }} {{ $alert->severity === AlertSeverity::CRITICAL && $alert->status !== AlertStatus::RESOLVED ? 'alert-row-critical' : '' }}">
                                <td>
                                    <x-status-badge :variant="BadgeHelper::variant($alert->severity)" :label="BadgeHelper::label($alert->severity)" />
                                </td>
                                <td>
                                    <div style="font-weight:600;">{{ $alert->title }}</div>
                                    <div class="td-muted">{{ $alert->message }}</div>
                                </td>
                                <td>{{ AlertType::tryFrom((string) $alert->type)?->label() ?? $alert->type }}</td>
                                <td>
                                    <x-status-badge :variant="BadgeHelper::variant($alert->status)" :label="BadgeHelper::label($alert->status)" />
                                </td>
                                <td>{{ $alert->created_at?->format('d/m/Y H:i') }}</td>
                                <td>
                                    @php $related = $alert->relatedRoute(); @endphp
                                    @if($related)
                                        <a class="btn btn-ghost btn-sm" href="{{ route($related['name'], $related['params']) }}">Abrir</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <a href="{{ route('alerts.show', $alert) }}" class="btn btn-ghost btn-sm">Detalhe</a>

                                        @if($alert->status === AlertStatus::OPEN)
                                            <form method="POST" action="{{ route('alerts.read', $alert) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-ghost btn-sm">Marcar lido</button>
                                            </form>
                                        @endif

                                        @if($alert->status !== AlertStatus::RESOLVED)
                                            <form method="POST" action="{{ route('alerts.resolve', $alert) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-secondary btn-sm">Resolver</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid var(--outline-variant);">
                <div class="text-muted" style="font-size:13px;">
                    Mostrando {{ $alerts->firstItem() }}-{{ $alerts->lastItem() }} de {{ $alerts->total() }}
                </div>
                <div style="display:flex;gap:8px;">
                    @if($alerts->onFirstPage())
                        <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Anterior</span>
                    @else
                        <a class="btn btn-ghost btn-sm" href="{{ $alerts->previousPageUrl() }}">Anterior</a>
                    @endif

                    @if($alerts->hasMorePages())
                        <a class="btn btn-ghost btn-sm" href="{{ $alerts->nextPageUrl() }}">Proxima</a>
                    @else
                        <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Proxima</span>
                    @endif
                </div>
            </div>
        @endif
    </x-card>
@endsection

@push('styles')
<style>
.alert-row-critical td {
    background: var(--error-container) !important;
}

.alert-row-resolved td {
    opacity: .74;
}

@media (max-width: 1200px) {
    .stat-grid {
        grid-template-columns: 1fr !important;
    }

    .alerts-filter-grid {
        grid-template-columns: 1fr 1fr 1fr !important;
    }
}

@media (max-width: 760px) {
    .alerts-filter-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.getElementById('alert-filter-form');
    var loading = document.getElementById('alerts-loading');

    if (!form || !loading) {
        return;
    }

    form.addEventListener('submit', function () {
        loading.style.display = 'block';
    });
})();
</script>
@endpush
