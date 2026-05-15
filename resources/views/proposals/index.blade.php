@extends('layouts.app')

@section('title', 'Propostas - VICZ Implementos')

@section('content')
    @php use App\Support\BadgeHelper; @endphp

    <x-page-header
        title="Propostas"
        subtitle="Pipeline comercial e previsão de entrada futura"
    >
        @can('create', \App\Models\Proposal::class)
            <a href="{{ route('proposals.create') }}" class="btn btn-primary">Nova proposta</a>
        @endcan
    </x-page-header>

    <div class="stat-grid" style="grid-template-columns:repeat(5,minmax(0,1fr));">
        <x-stat-card
            label="Propostas abertas"
            :value="$summaryCards['openProposals']"
            delta="pipeline ativo"
            trend="neutral"
            icon='<path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 16H5V8h14z"/>'
            iconVariant="navy"
        />

        <x-stat-card
            label="Fechamento no mês"
            :value="$summaryCards['closeThisMonth']"
            delta="prioridade comercial"
            trend="positive"
            icon='<path d="M12 8V4l-8 8 8 8v-4h8v-8z"/>'
            iconVariant="green"
        />

        <x-stat-card
            label="Forecast ponderado"
            value="R$ {{ number_format($summaryCards['weightedForecast'], 2, ',', '.') }}"
            delta="valor provável"
            trend="positive"
            icon='<path d="M12 2v10.17l3.59-3.58L17 10l-5 5-5-5 1.41-1.41L11 12.17V2zM5 18h14v2H5z"/>'
            iconVariant="green"
        />

        <x-stat-card
            label="Aprovado"
            value="R$ {{ number_format($summaryCards['approvedTotal'], 2, ',', '.') }}"
            delta="potencial convertido"
            trend="positive"
            icon='<path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/>'
            iconVariant="green"
        />

        <x-stat-card
            label="Perdido"
            value="R$ {{ number_format($summaryCards['lostTotal'], 2, ',', '.') }}"
            delta="fora do forecast"
            trend="negative"
            icon='<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>'
            iconVariant="red"
        />
    </div>

    <x-filter-bar label="Filtros">
        <form id="proposal-filter-form" method="GET" action="{{ route('proposals.index') }}" class="proposals-filter-grid" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr 1fr 1fr auto auto;gap:8px;width:100%;align-items:end;">
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
                <label class="form-label" for="sales_owner_id">Responsável</label>
                <select id="sales_owner_id" name="sales_owner_id" class="form-control form-select">
                    <option value="">Todos</option>
                    @foreach($salesOwners as $salesOwner)
                        <option value="{{ $salesOwner->id }}" @selected($filters['sales_owner_id'] === (string) $salesOwner->id)>{{ $salesOwner->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="close_from">Fechamento (de)</label>
                <input id="close_from" name="close_from" type="date" class="form-control" value="{{ $filters['close_from'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="close_to">Fechamento (até)</label>
                <input id="close_to" name="close_to" type="date" class="form-control" value="{{ $filters['close_to'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="probability_min">Prob. mín (%)</label>
                <input id="probability_min" name="probability_min" type="number" min="0" max="100" class="form-control" value="{{ $filters['probability_min'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="probability_max">Prob. máx (%)</label>
                <input id="probability_max" name="probability_max" type="number" min="0" max="100" class="form-control" value="{{ $filters['probability_max'] }}">
            </div>

            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('proposals.index') }}" class="btn btn-ghost">Limpar</a>
        </form>
    </x-filter-bar>

    <x-card title="Propostas ({{ $proposals->total() }})">
        <div id="proposal-loading" style="display:none;margin-bottom:16px;">
            <x-loading-state :rows="4" message="Aplicando filtros..." />
        </div>

        @if($proposals->isEmpty())
            <x-empty-state
                icon="proposal"
                title="Nenhuma proposta encontrada"
                message="Ajuste os filtros ou crie uma nova proposta."
            >
                @can('create', \App\Models\Proposal::class)
                    <a href="{{ route('proposals.create') }}" class="btn btn-primary btn-sm">Nova proposta</a>
                @endcan
            </x-empty-state>
        @else
            <div class="table-wrapper" style="border:none;border-radius:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Proposta</th>
                            <th>Cliente</th>
                            <th>Responsável</th>
                            <th>Fechamento</th>
                            <th>Probabilidade</th>
                            <th class="text-right">Valor</th>
                            <th class="text-right">Ponderado</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proposals as $proposal)
                            <tr>
                                <td>
                                    <strong>{{ $proposal->number }}</strong>
                                    @if($proposal->workOrder)
                                        <div class="td-muted">Convertida em {{ $proposal->workOrder->number }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $proposal->customer }}</div>
                                    <div class="td-muted">{{ $proposal->product_or_service_type }}</div>
                                </td>
                                <td>{{ $proposal->salesOwner?->name ?? '-' }}</td>
                                <td>{{ $proposal->expected_close_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <div class="proposal-probability-wrap">
                                        <div class="proposal-probability-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $proposal->close_probability }}">
                                            <div class="proposal-probability-fill" style="width:{{ $proposal->close_probability }}%;"></div>
                                        </div>
                                        <span class="proposal-probability-label">{{ $proposal->close_probability }}%</span>
                                    </div>
                                </td>
                                <td class="text-right td-mono">R$ {{ number_format((float) $proposal->amount, 2, ',', '.') }}</td>
                                <td class="text-right td-mono">R$ {{ number_format((float) $proposal->weighted_amount, 2, ',', '.') }}</td>
                                <td>
                                    <x-status-badge
                                        :variant="BadgeHelper::variant($proposal->status)"
                                        :label="BadgeHelper::label($proposal->status)"
                                    />
                                </td>
                                <td>
                                    <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-ghost btn-sm">Ver</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid var(--outline-variant);">
                <div class="text-muted" style="font-size:13px;">
                    Mostrando {{ $proposals->firstItem() }}-{{ $proposals->lastItem() }} de {{ $proposals->total() }}
                </div>
                <div style="display:flex;gap:8px;">
                    @if($proposals->onFirstPage())
                        <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Anterior</span>
                    @else
                        <a class="btn btn-ghost btn-sm" href="{{ $proposals->previousPageUrl() }}">Anterior</a>
                    @endif

                    @if($proposals->hasMorePages())
                        <a class="btn btn-ghost btn-sm" href="{{ $proposals->nextPageUrl() }}">Próxima</a>
                    @else
                        <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Próxima</span>
                    @endif
                </div>
            </div>
        @endif
    </x-card>

    @if($summaryCards['lostTotal'] > 0)
        <div class="mt-16">
            <x-error-state
                title="Perdas comerciais registradas"
                message="Propostas perdidas não entram no forecast ponderado. Revise motivos de perda no detalhe para melhorar conversão."
            />
        </div>
    @endif
@endsection

@push('styles')
<style>
.proposal-probability-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}

.proposal-probability-track {
    width: 94px;
    height: 8px;
    border-radius: var(--radius-full);
    background: var(--surface-container);
    overflow: hidden;
}

.proposal-probability-fill {
    height: 100%;
    background: var(--status-info);
}

.proposal-probability-label {
    font-size: 12px;
    color: var(--on-surface-variant);
    font-feature-settings: "tnum";
}

@media (max-width: 1300px) {
    .stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .proposals-filter-grid {
        grid-template-columns: 1fr 1fr 1fr 1fr !important;
    }
}

@media (max-width: 900px) {
    .stat-grid {
        grid-template-columns: 1fr !important;
    }

    .proposals-filter-grid {
        grid-template-columns: 1fr 1fr !important;
    }
}

@media (max-width: 640px) {
    .proposals-filter-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.getElementById('proposal-filter-form');
    var loading = document.getElementById('proposal-loading');

    if (!form || !loading) {
        return;
    }

    form.addEventListener('submit', function () {
        loading.style.display = 'block';
    });
})();
</script>
@endpush
