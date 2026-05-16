@extends('layouts.app')

@section('title', 'Clientes - VICZ Implementos')

@section('content')
    @php use App\Support\BadgeHelper; @endphp

    <x-page-header title="Clientes" subtitle="Cadastro mestre e historico financeiro/comercial">
        @can('create', \App\Models\Customer::class)
            <a href="{{ route('customers.create') }}" class="btn btn-primary">Novo cliente</a>
        @endcan
    </x-page-header>

    <x-filter-bar label="Filtros">
        <form method="GET" action="{{ route('customers.index') }}" class="customers-filter-grid" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr 1fr auto auto;gap:8px;align-items:end;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="search">Nome</label>
                <input id="search" name="search" type="text" class="form-control" value="{{ $filters['search'] }}" placeholder="Nome, razao social ou email">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="cpf">CPF</label>
                <input id="cpf" name="cpf" type="text" class="form-control" value="{{ $filters['cpf'] }}">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="cnpj">CNPJ</label>
                <input id="cnpj" name="cnpj" type="text" class="form-control" value="{{ $filters['cnpj'] }}">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="phone">Telefone/WhatsApp</label>
                <input id="phone" name="phone" type="text" class="form-control" value="{{ $filters['phone'] }}">
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
                <label class="form-label" for="type">Tipo</label>
                <select id="type" name="type" class="form-control form-select">
                    <option value="">Todos</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('customers.index') }}" class="btn btn-ghost">Limpar</a>
        </form>
    </x-filter-bar>

    <x-card title="Clientes ({{ $customers->total() }})">
        @if($customers->isEmpty())
            <x-empty-state icon="default" title="Nenhum cliente encontrado" message="Ajuste os filtros ou cadastre um cliente." />
        @else
            <div class="table-wrapper" style="border:none;border-radius:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Contato</th>
                            <th>Historico</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td>
                                    <div style="font-weight:600;">{{ $customer->name }}</div>
                                    <div class="td-muted">{{ $customer->cpf ?: ($customer->cnpj ?: '-') }}</div>
                                </td>
                                <td>{{ $customer->type->label() }}</td>
                                <td>
                                    @php
                                        $variant = $customer->status->value === 'ACTIVE' ? 'success' : ($customer->status->value === 'BLOCKED' ? 'danger' : 'neutral');
                                    @endphp
                                    <x-status-badge :variant="$variant" :label="$customer->status->label()" />
                                </td>
                                <td>
                                    <div>{{ $customer->phone ?: '-' }}</div>
                                    <div class="td-muted">{{ $customer->email ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="td-muted">OS: {{ $customer->work_orders_count }} | Propostas: {{ $customer->proposals_count }}</div>
                                    <div class="td-muted" style="color:{{ $customer->overdue_receivables_count > 0 ? 'var(--error)' : 'var(--on-surface-variant)' }};">
                                        Recebiveis vencidos: {{ $customer->overdue_receivables_count }}
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost btn-sm">Ver</a>
                                        @can('update', $customer)
                                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-secondary btn-sm">Editar</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
@endsection

@push('styles')
<style>
@media (max-width: 1100px) {
    .customers-filter-grid {
        grid-template-columns: 1fr 1fr 1fr 1fr !important;
    }
}

@media (max-width: 700px) {
    .customers-filter-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush
