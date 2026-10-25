@extends('layouts.app')

@section('title', 'Contas a Receber - VICZ Implementos')

@section('content')
    @php use App\Support\BadgeHelper; @endphp

    <x-page-header
        title="Contas a Receber"
        subtitle="Controle de receitas previstas, recebidas e em atraso"
    >
        @can('create', \App\Models\AccountReceivable::class)
            <a href="{{ route('receivables.create') }}" class="btn btn-primary">Novo recebível</a>
        @endcan
    </x-page-header>

    <div class="stat-grid">
        <x-stat-card
            label="Previsto hoje"
            value="R$ {{ number_format($summaryCards['todayExpected'], 2, ',', '.') }}"
            delta="vencimentos do dia"
            trend="neutral"
            icon='<path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 16H5V8h14z"/>'
            iconVariant="navy"
        />

        <x-stat-card
            label="Próximos 7 dias"
            value="R$ {{ number_format($summaryCards['next7Expected'], 2, ',', '.') }}"
            delta="pipeline curto prazo"
            trend="positive"
            icon='<path d="M12 8V4l-8 8 8 8v-4h8v-8z"/>'
            iconVariant="green"
        />

        <x-stat-card
            label="Em atraso"
            value="R$ {{ number_format($summaryCards['overdueTotal'], 2, ',', '.') }}"
            :delta="$summaryCards['overdueTotal'] > 0 ? 'cobrança ativa' : 'sem pendências'"
            trend="{{ $summaryCards['overdueTotal'] > 0 ? 'negative' : 'positive' }}"
            icon='<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>'
            iconVariant="{{ $summaryCards['overdueTotal'] > 0 ? 'red' : 'green' }}"
        />

        <x-stat-card
            label="Recebido no período"
            value="R$ {{ number_format($summaryCards['receivedInPeriod'], 2, ',', '.') }}"
            delta="baixas confirmadas"
            trend="positive"
            icon='<path d="M12 2v10.17l3.59-3.58L17 10l-5 5-5-5 1.41-1.41L11 12.17V2zM5 18h14v2H5z"/>'
            iconVariant="green"
        />

        <x-stat-card
            label="Saldo aberto clientes"
            value="R$ {{ number_format($summaryCards['openCustomerBalance'], 2, ',', '.') }}"
            :delta="$summaryCards['openCustomersCount'] . ' cliente(s)'"
            trend="neutral"
            icon='<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.98 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>'
            iconVariant="amber"
        />
    </div>

    <x-filter-bar label="Filtros">
        <form method="GET" action="{{ route('receivables.index') }}" class="receivables-filter-grid filter-grid">
            <x-sort-inputs :sorting="$sorting" />
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="customer_id">Cliente</label>
                <select id="customer_id" name="customer_id" class="form-control form-select">
                    <option value="">Todos os clientes</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected($filters['customer_id'] === (string) $customer->id)>{{ $customer->name }}</option>
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
                <label class="form-label" for="expected_from">Previsão (de)</label>
                <input id="expected_from" name="expected_from" type="date" class="form-control" value="{{ $filters['expected_from'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="expected_to">Previsão (até)</label>
                <input id="expected_to" name="expected_to" type="date" class="form-control" value="{{ $filters['expected_to'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="payment_method">Pagamento</label>
                <select id="payment_method" name="payment_method" class="form-control form-select">
                    <option value="">Todos</option>
                    @foreach($paymentMethods as $paymentMethod)
                        <option value="{{ $paymentMethod->value }}" @selected($filters['payment_method'] === $paymentMethod->value)>{{ $paymentMethod->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="work_order_id">Ordem de Serviço</label>
                <select id="work_order_id" name="work_order_id" class="form-control form-select">
                    <option value="">Todas</option>
                    @foreach($workOrders as $workOrder)
                        <option value="{{ $workOrder->id }}" @selected($filters['work_order_id'] === (string) $workOrder->id)>{{ $workOrder->number }} - {{ $workOrder->customer }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('receivables.index') }}" class="btn btn-ghost">Limpar</a>
        </form>
    </x-filter-bar>

    <div class="receivables-layout layout-aside layout-aside--list" style="gap:16px;align-items:start;">
        <x-card title="Recebíveis ({{ $receivables->total() }})">
            @if($receivables->isEmpty())
                <x-empty-state
                    icon="default"
                    title="Nenhum recebível encontrado"
                    message="Ajuste os filtros ou cadastre um novo recebível."
                />
            @else
                <div class="table-wrapper" style="border:none;border-radius:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <x-sort-th column="customer" label="Cliente" :sorting="$sorting" />
                                <x-sort-th column="description" label="Descrição" :sorting="$sorting" />
                                <x-sort-th column="type" label="Tipo" :sorting="$sorting" />
                                <th>OS</th>
                                <x-sort-th column="expected_date" label="Data prevista" :sorting="$sorting" />
                                <x-sort-th column="received_date" label="Recebido em" :sorting="$sorting" />
                                <x-sort-th column="amount" label="Valor" :sorting="$sorting" class="text-right" />
                                <x-sort-th column="status" label="Status" :sorting="$sorting" />
                                <x-sort-th column="payment_method" label="Pagamento" :sorting="$sorting" />
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receivables as $receivable)
                                <tr>
                                    <td>
                                        <strong>{{ $receivable->customer }}</strong>
                                    </td>
                                    <td>
                                        <div>{{ $receivable->description }}</div>
                                        @if($receivable->status === \App\Enums\ReceivableStatus::OVERDUE)
                                            <div class="td-muted" style="color:var(--error);">Atrasado desde {{ $receivable->expected_date->format('d/m/Y') }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $receivable->receivable_type->label() }}</td>
                                    <td class="td-mono">{{ $receivable->work_order_number ?? '-' }}</td>
                                    <td>{{ $receivable->expected_date->format('d/m/Y') }}</td>
                                    <td>{{ $receivable->received_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="text-right td-mono">R$ {{ number_format((float) $receivable->expected_amount, 2, ',', '.') }}</td>
                                    <td>
                                        <x-status-badge
                                            :variant="BadgeHelper::variant($receivable->status)"
                                            :label="BadgeHelper::label($receivable->status)"
                                        />
                                    </td>
                                    <td>{{ $receivable->payment_method?->label() ?? '-' }}</td>
                                    <td>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            <a href="{{ route('receivables.show', $receivable) }}" class="btn btn-ghost btn-sm">Ver</a>
                                            @can('update', $receivable)
                                                @if(in_array($receivable->status, [\App\Enums\ReceivableStatus::EXPECTED, \App\Enums\ReceivableStatus::OVERDUE], true))
                                                    <a href="{{ route('receivables.edit', $receivable) }}" class="btn btn-secondary btn-sm">Editar</a>
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
                        Mostrando {{ $receivables->firstItem() }}-{{ $receivables->lastItem() }} de {{ $receivables->total() }}
                    </div>
                    <div style="display:flex;gap:8px;">
                        @if($receivables->onFirstPage())
                            <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Anterior</span>
                        @else
                            <a class="btn btn-ghost btn-sm" href="{{ $receivables->previousPageUrl() }}">Anterior</a>
                        @endif

                        @if($receivables->hasMorePages())
                            <a class="btn btn-ghost btn-sm" href="{{ $receivables->nextPageUrl() }}">Próxima</a>
                        @else
                            <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Próxima</span>
                        @endif
                    </div>
                </div>
            @endif
        </x-card>

        <x-card title="Clientes em Atraso">
            @if($overdueCustomers->isEmpty())
                <x-empty-state icon="alert" title="Sem atrasos" message="Não há clientes com recebíveis vencidos." />
            @else
                <div style="display:flex;flex-direction:column;gap:0;">
                    @foreach($overdueCustomers as $customer)
                        <div style="padding:12px 20px;border-bottom:1px solid var(--outline-variant);">
                            <div style="font-size:13px;font-weight:600;color:var(--on-surface);">{{ $customer->customer }}</div>
                            <div class="td-muted" style="display:flex;justify-content:space-between;margin-top:2px;">
                                <span>{{ $customer->receivables_count }} lançamento(s)</span>
                                <span class="td-mono" style="color:var(--error);font-weight:600;">R$ {{ number_format((float) $customer->overdue_total, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
@endsection

