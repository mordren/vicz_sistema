@extends('layouts.app')

@section('title', 'Ordens de Servico - VICZ Implementos')

@section('content')
    @php use App\Support\BadgeHelper; @endphp

    <x-page-header
        title="Ordens de Servico"
        subtitle="Producao, entrega e resultado financeiro por ordem"
    >
        @can('create', \App\Models\WorkOrder::class)
            <a href="{{ route('work-orders.create') }}" class="btn btn-primary">Nova OS</a>
        @endcan
    </x-page-header>

    <div class="stat-grid" style="grid-template-columns:repeat(5,minmax(0,1fr));">
        <x-stat-card
            label="OS ativas"
            :value="$summaryCards['totalOpen']"
            delta="em operacao"
            trend="neutral"
            icon='<path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 16H5V8h14z"/>'
            iconVariant="navy"
        />

        <x-stat-card
            label="Entrega em 3 dias"
            :value="$summaryCards['dueIn3Days']"
            delta="atenção operacional"
            trend="positive"
            icon='<path d="M12 8V4l-8 8 8 8v-4h8v-8z"/>'
            iconVariant="green"
        />

        <x-stat-card
            label="OS em atraso"
            :value="$summaryCards['overdue']"
            :delta="$summaryCards['overdue'] > 0 ? 'risco de entrega' : 'sem atrasos'"
            :trend="$summaryCards['overdue'] > 0 ? 'negative' : 'positive'"
            icon='<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>'
            :iconVariant="$summaryCards['overdue'] > 0 ? 'red' : 'green'"
        />

        <x-stat-card
            label="Contratado"
            value="R$ {{ number_format($summaryCards['contractedTotal'], 2, ',', '.') }}"
            delta="receita total"
            trend="positive"
            icon='<path d="M12 2v10.17l3.59-3.58L17 10l-5 5-5-5 1.41-1.41L11 12.17V2zM5 18h14v2H5z"/>'
            iconVariant="green"
        />

        <x-stat-card
            label="Saldo em aberto"
            value="R$ {{ number_format($summaryCards['openBalance'], 2, ',', '.') }}"
            delta="a receber"
            trend="neutral"
            icon='<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.98 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>'
            iconVariant="amber"
        />
    </div>

    <x-filter-bar label="Filtros">
        <form id="work-order-filter-form" method="GET" action="{{ route('work-orders.index') }}" class="work-orders-filter-grid" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr 1fr auto auto;gap:8px;width:100%;align-items:end;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="customer">Cliente</label>
                <input id="customer" name="customer" type="text" class="form-control" value="{{ $filters['customer'] }}" placeholder="Nome do cliente">
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
                <label class="form-label" for="responsible_user_id">Responsável</label>
                <select id="responsible_user_id" name="responsible_user_id" class="form-control form-select">
                    <option value="">Todos</option>
                    @foreach($responsibleUsers as $responsible)
                        <option value="{{ $responsible->id }}" @selected($filters['responsible_user_id'] === (string) $responsible->id)>{{ $responsible->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="delivery_from">Entrega (de)</label>
                <input id="delivery_from" name="delivery_from" type="date" class="form-control" value="{{ $filters['delivery_from'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="delivery_to">Entrega (até)</label>
                <input id="delivery_to" name="delivery_to" type="date" class="form-control" value="{{ $filters['delivery_to'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="overdue_only">Atraso</label>
                <select id="overdue_only" name="overdue_only" class="form-control form-select">
                    <option value="0" @selected(!$filters['overdue_only'])>Todos</option>
                    <option value="1" @selected($filters['overdue_only'])>Somente atrasados</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('work-orders.index') }}" class="btn btn-ghost">Limpar</a>
        </form>
    </x-filter-bar>

    <x-card title="Lista de Ordens de Serviço ({{ $workOrders->total() }})">
        <div id="work-order-loading" style="display:none;margin-bottom:16px;">
            <x-loading-state :rows="4" message="Aplicando filtros..." />
        </div>

        @if($workOrders->isEmpty())
            <x-empty-state
                icon="workorder"
                title="Nenhuma OS encontrada"
                message="Ajuste os filtros ou crie uma nova ordem de serviço."
            >
                @can('create', \App\Models\WorkOrder::class)
                    <a href="{{ route('work-orders.create') }}" class="btn btn-primary btn-sm">Nova OS</a>
                @endcan
            </x-empty-state>
        @else
            <div class="table-wrapper" style="border:none;border-radius:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>OS</th>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th>Responsável</th>
                            <th>Progresso</th>
                            <th>Entrega</th>
                            <th class="text-right">Saldo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($workOrders as $workOrder)
                            @php
                                $highlight = $highlightMap[$workOrder->id] ?? ['is_overdue' => false, 'is_near_delivery' => false];
                                $deadline = $workOrder->recalculated_delivery_date ?? $workOrder->expected_delivery_date;
                            @endphp
                            <tr class="{{ $highlight['is_overdue'] ? 'wo-row-overdue' : ($highlight['is_near_delivery'] ? 'wo-row-near' : '') }}">
                                <td>
                                    <strong>{{ $workOrder->number }}</strong>
                                    <div class="td-muted">Abertura: {{ $workOrder->opened_at?->format('d/m/Y') }}</div>
                                </td>
                                <td>{{ $workOrder->customer }}</td>
                                <td>{{ $workOrder->service_type }}</td>
                                <td>{{ $workOrder->responsibleUser?->name ?? '-' }}</td>
                                <td>
                                    <div class="wo-progress-wrap">
                                        <div class="wo-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $workOrder->progress_percentage }}">
                                            <div class="wo-progress-fill" style="width:{{ $workOrder->progress_percentage }}%;"></div>
                                        </div>
                                        <span class="wo-progress-text">{{ $workOrder->progress_percentage }}%</span>
                                    </div>
                                </td>
                                <td>
                                    {{ $deadline?->format('d/m/Y') ?? '-' }}
                                    @if($highlight['is_overdue'])
                                        <div class="td-muted" style="color:var(--error);">Em atraso</div>
                                    @elseif($highlight['is_near_delivery'])
                                        <div class="td-muted" style="color:var(--secondary);">Próxima da entrega</div>
                                    @endif
                                </td>
                                <td class="text-right td-mono">R$ {{ number_format((float) $workOrder->receivable_balance, 2, ',', '.') }}</td>
                                <td>
                                    <x-status-badge
                                        :variant="BadgeHelper::variant($workOrder->status)"
                                        :label="BadgeHelper::label($workOrder->status)"
                                    />
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-ghost btn-sm">Ver</a>
                                        @can('update', $workOrder)
                                            @if(!in_array($workOrder->status, [\App\Enums\WorkOrderStatus::DELIVERED, \App\Enums\WorkOrderStatus::CANCELLED], true))
                                                <a href="{{ route('work-orders.edit', $workOrder) }}" class="btn btn-secondary btn-sm">Editar</a>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid var(--outline-variant);">
                <div class="text-muted" style="font-size:13px;">
                    Mostrando {{ $workOrders->firstItem() }}-{{ $workOrders->lastItem() }} de {{ $workOrders->total() }}
                </div>
                <div style="display:flex;gap:8px;">
                    @if($workOrders->onFirstPage())
                        <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Anterior</span>
                    @else
                        <a class="btn btn-ghost btn-sm" href="{{ $workOrders->previousPageUrl() }}">Anterior</a>
                    @endif

                    @if($workOrders->hasMorePages())
                        <a class="btn btn-ghost btn-sm" href="{{ $workOrders->nextPageUrl() }}">Próxima</a>
                    @else
                        <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Próxima</span>
                    @endif
                </div>
            </div>
        @endif
    </x-card>

    @if($summaryCards['overdue'] > 0)
        <div class="mt-16">
            <x-error-state
                title="Risco de entrega detectado"
                message="Existem ordens de serviço vencidas. Priorize as OS sinalizadas em vermelho para evitar impacto no cronograma e no caixa."
            />
        </div>
    @endif
@endsection

@push('styles')
<style>
.wo-row-overdue td {
    background: var(--error-container) !important;
}

.wo-row-near td {
    background: #e6f4ea !important;
}

.wo-progress-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}

.wo-progress-track {
    width: 92px;
    height: 8px;
    border-radius: var(--radius-full);
    background: var(--surface-container);
    overflow: hidden;
}

.wo-progress-fill {
    height: 100%;
    background: var(--status-info);
}

.wo-progress-text {
    font-size: 12px;
    color: var(--on-surface-variant);
    font-feature-settings: "tnum";
}

@media (max-width: 1200px) {
    .stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .work-orders-filter-grid {
        grid-template-columns: 1fr 1fr 1fr 1fr !important;
    }
}

@media (max-width: 860px) {
    .stat-grid {
        grid-template-columns: 1fr !important;
    }

    .work-orders-filter-grid {
        grid-template-columns: 1fr 1fr !important;
    }
}

@media (max-width: 640px) {
    .work-orders-filter-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.getElementById('work-order-filter-form');
    var loading = document.getElementById('work-order-loading');

    if (!form || !loading) {
        return;
    }

    form.addEventListener('submit', function () {
        loading.style.display = 'block';
    });
})();
</script>
@endpush
