@extends('layouts.app')

@section('title', 'Contas a Pagar - VICZ Implementos')

@section('content')
    @php use App\Support\BadgeHelper; @endphp

    <x-page-header
        title="Contas a Pagar"
        subtitle="Controle de obrigacoes previstas, vencidas e pagas"
    >
        @can('create', \App\Models\AccountPayable::class)
            <a href="{{ route('payables.create') }}" class="btn btn-primary">Nova conta</a>
        @endcan
    </x-page-header>

    <div class="stat-grid">
        <x-stat-card
            label="Saldo em aberto"
            value="R$ {{ number_format($summaryCards['openTotal'], 2, ',', '.') }}"
            delta="titulos abertos"
            trend="neutral"
            icon='<path d="M12 1 3 5v6c0 5.25 3.75 10.74 9 12 5.25-1.26 9-6.75 9-12V5l-9-4zm1 17h-2v-2h2v2zm0-4h-2V8h2v6z"/>'
            iconVariant="amber"
        />

        <x-stat-card
            label="Vence hoje"
            value="R$ {{ number_format($summaryCards['dueToday'], 2, ',', '.') }}"
            delta="urgencia imediata"
            trend="negative"
            icon='<path d="M19 3h-1V1h-2v2H8V1H6v2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zm0 16H5V8h14z"/>'
            iconVariant="red"
        />

        <x-stat-card
            label="Proximos 7 dias"
            value="R$ {{ number_format($summaryCards['dueNext7'], 2, ',', '.') }}"
            delta="previsao curta"
            trend="neutral"
            icon='<path d="M12 8V4l-8 8 8 8v-4h8v-8z"/>'
            iconVariant="navy"
        />

        <x-stat-card
            label="Vencido"
            value="R$ {{ number_format($summaryCards['overdueTotal'], 2, ',', '.') }}"
            :delta="$summaryCards['overdueTotal'] > 0 ? 'requer acao' : 'sem pendencias'"
            trend="{{ $summaryCards['overdueTotal'] > 0 ? 'negative' : 'positive' }}"
            icon='<path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>'
            iconVariant="{{ $summaryCards['overdueTotal'] > 0 ? 'red' : 'green' }}"
        />

        <x-stat-card
            label="Pago no periodo"
            value="R$ {{ number_format($summaryCards['paidInPeriod'], 2, ',', '.') }}"
            delta="fluxo de saida"
            trend="positive"
            icon='<path d="M5 13l4 4L19 7"/>'
            iconVariant="green"
        />
    </div>

    <x-filter-bar label="Filtros">
        <form method="GET" action="{{ route('payables.index') }}" class="payables-filter-grid filter-grid">
            <x-sort-inputs :sorting="$sorting" />
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="supplier">Fornecedor</label>
                <input id="supplier" name="supplier" type="text" class="form-control" value="{{ $filters['supplier'] }}" placeholder="Nome do fornecedor">
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
                <label class="form-label" for="due_from">Vencimento (de)</label>
                <input id="due_from" name="due_from" type="date" class="form-control" value="{{ $filters['due_from'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="due_to">Vencimento (ate)</label>
                <input id="due_to" name="due_to" type="date" class="form-control" value="{{ $filters['due_to'] }}">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label" for="category">Categoria</label>
                <select id="category" name="category" class="form-control form-select">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->value }}" @selected($filters['category'] === $category->value)>{{ $category->label() }}</option>
                    @endforeach
                </select>
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
                <label class="form-label" for="work_order_id">Ordem de Servico</label>
                <select id="work_order_id" name="work_order_id" class="form-control form-select">
                    <option value="">Todas</option>
                    @foreach($workOrders as $workOrder)
                        <option value="{{ $workOrder->id }}" @selected($filters['work_order_id'] === (string) $workOrder->id)>{{ $workOrder->number }} - {{ $workOrder->customer }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('payables.index') }}" class="btn btn-ghost">Limpar</a>
        </form>
    </x-filter-bar>

    <div class="payables-layout layout-aside layout-aside--list" style="gap:16px;align-items:start;">
        <x-card title="Contas ({{ $payables->total() }})">
            @if($payables->isEmpty())
                <x-empty-state
                    icon="payable"
                    title="Nenhuma conta encontrada"
                    message="Ajuste os filtros ou cadastre uma nova conta a pagar."
                />
            @else
                <div class="table-wrapper" style="border:none;border-radius:0;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <x-sort-th column="supplier" label="Fornecedor" :sorting="$sorting" />
                                <x-sort-th column="description" label="Descrição" :sorting="$sorting" />
                                <x-sort-th column="category" label="Categoria" :sorting="$sorting" />
                                <x-sort-th column="installment" label="Parcela" :sorting="$sorting" />
                                <th>OS</th>
                                <x-sort-th column="due_date" label="Vencimento" :sorting="$sorting" />
                                <x-sort-th column="payment_date" label="Pago em" :sorting="$sorting" />
                                <x-sort-th column="amount" label="Valor" :sorting="$sorting" class="text-right" />
                                <x-sort-th column="status" label="Status" :sorting="$sorting" />
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payables as $payable)
                                <tr>
                                    <td><strong>{{ $payable->supplier }}</strong></td>
                                    <td>
                                        <div>{{ $payable->description }}</div>
                                        @if($payable->status === \App\Enums\PayableStatus::OVERDUE)
                                            <div class="td-muted" style="color:var(--error);">Vencido desde {{ $payable->due_date->format('d/m/Y') }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $payable->category->label() }}</td>
                                    <td>{{ $payable->installment_number }}/{{ $payable->total_installments }}</td>
                                    <td class="td-mono">{{ $payable->workOrder?->number ?? '-' }}</td>
                                    <td>{{ $payable->due_date->format('d/m/Y') }}</td>
                                    <td>{{ $payable->payment_date?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="text-right td-mono">R$ {{ number_format((float) $payable->amount, 2, ',', '.') }}</td>
                                    <td>
                                        <x-status-badge
                                            :variant="BadgeHelper::variant($payable->status)"
                                            :label="BadgeHelper::label($payable->status)"
                                        />
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            <a href="{{ route('payables.show', $payable) }}" class="btn btn-ghost btn-sm">Ver</a>
                                            @can('update', $payable)
                                                @if(in_array($payable->status, [\App\Enums\PayableStatus::OPEN, \App\Enums\PayableStatus::OVERDUE], true))
                                                    <a href="{{ route('payables.edit', $payable) }}" class="btn btn-secondary btn-sm">Editar</a>
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
                        Mostrando {{ $payables->firstItem() }}-{{ $payables->lastItem() }} de {{ $payables->total() }}
                    </div>
                    <div style="display:flex;gap:8px;">
                        @if($payables->onFirstPage())
                            <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Anterior</span>
                        @else
                            <a class="btn btn-ghost btn-sm" href="{{ $payables->previousPageUrl() }}">Anterior</a>
                        @endif

                        @if($payables->hasMorePages())
                            <a class="btn btn-ghost btn-sm" href="{{ $payables->nextPageUrl() }}">Proxima</a>
                        @else
                            <span class="btn btn-ghost btn-sm" style="opacity:.55;pointer-events:none;">Proxima</span>
                        @endif
                    </div>
                </div>
            @endif
        </x-card>

        <x-card title="Fornecedores em Atraso">
            @if($overdueSuppliers->isEmpty())
                <x-empty-state icon="alert" title="Sem atrasos" message="Nao ha fornecedores com contas vencidas." />
            @else
                <div style="display:flex;flex-direction:column;gap:0;">
                    @foreach($overdueSuppliers as $supplier)
                        <div style="padding:12px 20px;border-bottom:1px solid var(--outline-variant);">
                            <div style="font-size:13px;font-weight:600;color:var(--on-surface);">{{ $supplier->supplier }}</div>
                            <div class="td-muted" style="display:flex;justify-content:space-between;margin-top:2px;">
                                <span>{{ $supplier->payables_count }} titulo(s)</span>
                                <span class="td-mono" style="color:var(--error);font-weight:600;">R$ {{ number_format((float) $supplier->overdue_total, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
@endsection

