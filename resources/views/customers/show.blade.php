@extends('layouts.app')

@section('title', 'Detalhe do Cliente - VICZ Implementos')

@section('content')
    <x-page-header title="Cliente #{{ $customer->id }}" subtitle="Historico financeiro, comercial e de servicos">
        @can('update', $customer)
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-secondary">Editar</a>
        @endcan
        @can('delete', $customer)
            @if($customer->workOrders->isEmpty() && $customer->proposals->isEmpty() && $customer->receivables->isEmpty())
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Excluir este cliente permanentemente?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            @endif
        @endcan
        <a href="{{ route('customers.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    <div class="stat-grid" style="grid-template-columns:repeat(4,minmax(0,1fr));">
        <x-stat-card label="Total contratado" value="R$ {{ number_format($summary['total_contracted'], 2, ',', '.') }}" delta="ordens de servico" trend="neutral" icon='<path d="M3 13h2v-2H3v2zm4 0h14v-2H7v2z"/>' iconVariant="navy" />
        <x-stat-card label="Total recebido" value="R$ {{ number_format($summary['total_received'], 2, ',', '.') }}" delta="historico de recebimentos" trend="positive" icon='<path d="M5 13l4 4L19 7"/>' iconVariant="green" />
        <x-stat-card label="Total vencido" value="R$ {{ number_format($summary['total_overdue'], 2, ',', '.') }}" delta="risco financeiro" trend="negative" icon='<path d="M1 21h22L12 2 1 21z"/>' iconVariant="red" />
        <x-stat-card label="Saldo pendente" value="R$ {{ number_format($summary['pending_balance'], 2, ',', '.') }}" delta="recebiveis abertos" trend="neutral" icon='<path d="M12 8V4l-8 8 8 8v-4h8v-8z"/>' iconVariant="amber" />
    </div>

    <div class="customer-detail-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;">
        <x-card title="Cadastro">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div><div class="form-label">Nome</div><div>{{ $customer->name }}</div></div>
                <div><div class="form-label">Tipo</div><div>{{ $customer->type->label() }}</div></div>
                <div><div class="form-label">CPF</div><div>{{ $customer->cpf ?: '-' }}</div></div>
                <div><div class="form-label">CNPJ</div><div>{{ $customer->cnpj ?: '-' }}</div></div>
                <div><div class="form-label">Email</div><div>{{ $customer->email ?: '-' }}</div></div>
                <div><div class="form-label">Contato</div><div>{{ $customer->contact_person ?: '-' }}</div></div>
                <div><div class="form-label">Telefone</div><div>{{ $customer->phone ?: '-' }}</div></div>
                <div><div class="form-label">WhatsApp</div><div>{{ $customer->whatsapp ?: '-' }}</div></div>
                <div style="grid-column:1/-1;"><div class="form-label">Endereco</div><div>{{ trim(($customer->street ?: '').' '.($customer->number ?: '')) ?: '-' }} {{ $customer->city ? '- '.$customer->city : '' }} {{ $customer->state ? '/ '.$customer->state : '' }}</div></div>
            </div>
        </x-card>

        <x-card title="Recebiveis em atraso">
            @if($history['overdue_receivables']->isEmpty())
                <x-empty-state icon="alert" title="Sem atraso" message="Nao ha recebiveis vencidos para este cliente." />
            @else
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($history['overdue_receivables'] as $item)
                        <div style="border:1px solid var(--outline-variant);border-radius:var(--radius-sm);padding:10px;">
                            <div style="font-weight:600;">{{ $item->description }}</div>
                            <div class="td-muted">Vencimento: {{ $item->expected_date?->format('d/m/Y') }} • R$ {{ number_format((float) $item->expected_amount, 2, ',', '.') }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card title="Historico de servicos (OS)">
            @if($customer->workOrders->isEmpty())
                <x-empty-state icon="workorder" title="Sem ordens de servico" message="Nenhuma OS vinculada a este cliente." />
            @else
                <div class="table-wrapper" style="border:none;border-radius:0;">
                    <table class="data-table">
                        <thead><tr><th>OS</th><th>Status</th><th>Contratado</th><th>Recebido</th></tr></thead>
                        <tbody>
                        @foreach($customer->workOrders as $wo)
                            <tr>
                                <td><a href="{{ route('work-orders.show', $wo) }}" class="btn btn-ghost btn-sm">{{ $wo->number }}</a></td>
                                <td>{{ $wo->status->label() }}</td>
                                <td class="td-mono">R$ {{ number_format((float) $wo->contracted_amount, 2, ',', '.') }}</td>
                                <td class="td-mono">R$ {{ number_format((float) $wo->received_amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card title="Historico comercial (Propostas)">
            @if($customer->proposals->isEmpty())
                <x-empty-state icon="proposal" title="Sem propostas" message="Nenhuma proposta vinculada a este cliente." />
            @else
                <div class="table-wrapper" style="border:none;border-radius:0;">
                    <table class="data-table">
                        <thead><tr><th>Proposta</th><th>Status</th><th>Valor</th><th>Fechamento</th></tr></thead>
                        <tbody>
                        @foreach($customer->proposals as $proposal)
                            <tr>
                                <td><a href="{{ route('proposals.show', $proposal) }}" class="btn btn-ghost btn-sm">{{ $proposal->number }}</a></td>
                                <td>{{ $proposal->status->label() }}</td>
                                <td class="td-mono">R$ {{ number_format((float) $proposal->amount, 2, ',', '.') }}</td>
                                <td>{{ $proposal->expected_close_date?->format('d/m/Y') ?: '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
@endsection

@push('styles')
<style>
@media (max-width: 980px) {
    .stat-grid {
        grid-template-columns: 1fr !important;
    }

    .customer-detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
