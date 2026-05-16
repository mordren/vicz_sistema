@extends('layouts.app')

@section('title', 'Relatorios - VICZ Implementos')

@section('content')
    <x-page-header
        title="{{ $scopeTitle }}"
        subtitle="Pre-visualizacao e exportacao com filtros"
    />

    <x-filter-bar label="Filtros do relatorio">
        <form id="report-filter-form" method="GET" action="{{ request()->url() }}" class="report-filter-grid" style="display:grid;grid-template-columns:2fr 1fr 1fr 1.5fr 1.5fr 2fr auto auto;gap:8px;width:100%;align-items:end;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="report">Relatorio</label>
                <select id="report" name="report" class="form-control form-select">
                    @foreach($reportOptions as $key => $label)
                        <option value="{{ $key }}" @selected($selectedReport === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="date_from">Periodo (de)</label>
                <input id="date_from" name="date_from" type="date" class="form-control" value="{{ $filters['date_from'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="date_to">Periodo (ate)</label>
                <input id="date_to" name="date_to" type="date" class="form-control" value="{{ $filters['date_to'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="entity">Entidade</label>
                <input id="entity" name="entity" type="text" class="form-control" value="{{ $filters['entity'] }}" placeholder="Cliente/Fornecedor">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="status">Status</label>
                <input id="status" name="status" type="text" class="form-control" value="{{ $filters['status'] }}" placeholder="Ex.: OPEN, APPROVED">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Exportacao</label>
                <a href="{{ route($exportRoute, request()->query()) }}" class="btn btn-secondary" style="width:100%;">Exportar CSV</a>
            </div>

            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="{{ request()->url() }}" class="btn btn-ghost">Limpar</a>
        </form>
    </x-filter-bar>

    <x-card title="{{ $report['title'] }}">
        <div id="report-loading" style="display:none;margin-bottom:16px;">
            <x-loading-state :rows="4" message="Gerando pre-visualizacao..." />
        </div>

        @if(count($report['rows']) === 0)
            <x-empty-state icon="report" title="Sem dados para o filtro" message="Ajuste o periodo, entidade ou status para visualizar informacoes." />
        @else
            <div class="table-wrapper" style="border:none;border-radius:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            @foreach($report['columns'] as $columnLabel)
                                <th>{{ $columnLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['rows'] as $row)
                            <tr>
                                @foreach(array_keys($report['columns']) as $columnKey)
                                    @php $value = $row[$columnKey] ?? ''; @endphp
                                    <td class="{{ is_numeric($value) ? 'td-mono' : '' }}">
                                        @if(is_float($value) || is_int($value))
                                            {{ number_format((float) $value, 2, ',', '.') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    <x-card title="Totais" class="mt-16">
        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;">
            @foreach($report['totals'] as $label => $value)
                <div style="border:1px solid var(--outline-variant);border-radius:var(--radius-sm);padding:12px;">
                    <div class="form-label">{{ str_replace('_', ' ', ucfirst($label)) }}</div>
                    <div class="td-mono" style="font-size:16px;font-weight:600;">
                        @if(is_float($value) || is_int($value))
                            {{ number_format((float) $value, 2, ',', '.') }}
                        @else
                            {{ $value }}
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
@endsection

@push('styles')
<style>
@media (max-width: 1200px) {
    .report-filter-grid {
        grid-template-columns: 1fr 1fr 1fr !important;
    }
}

@media (max-width: 760px) {
    .report-filter-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.getElementById('report-filter-form');
    var loading = document.getElementById('report-loading');

    if (!form || !loading) {
        return;
    }

    form.addEventListener('submit', function () {
        loading.style.display = 'block';
    });
})();
</script>
@endpush
