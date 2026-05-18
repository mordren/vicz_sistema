@extends('layouts.app')

@section('title', 'Detalhe da Conta a Receber - VICZ Implementos')

@section('content')
    @php
        use App\Enums\ReceivableStatus;
        use App\Support\BadgeHelper;

        $canEdit = auth()->user()->can('update', $receivable)
            && in_array($receivable->status, [ReceivableStatus::EXPECTED, ReceivableStatus::OVERDUE], true);

        $canReceive = auth()->user()->can('markAsReceived', $receivable)
            && in_array($receivable->status, [ReceivableStatus::EXPECTED, ReceivableStatus::OVERDUE], true);

        $canCancel = auth()->user()->can('update', $receivable)
            && in_array($receivable->status, [ReceivableStatus::EXPECTED, ReceivableStatus::OVERDUE], true);
    @endphp

    <x-page-header
        title="Recebível #{{ $receivable->id }}"
        subtitle="Detalhes, baixa e auditoria do lançamento"
    >
        @if($canEdit)
            <a href="{{ route('receivables.edit', $receivable) }}" class="btn btn-secondary">Editar</a>
        @endif
        <a href="{{ route('receivables.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    <div class="receivable-show-layout" style="display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start;">
        <x-card title="Dados do Recebível">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <div class="form-label">Cliente</div>
                    <div>{{ $receivable->customer }}</div>
                </div>
                <div>
                    <div class="form-label">Status</div>
                    <x-status-badge :variant="BadgeHelper::variant($receivable->status)" :label="BadgeHelper::label($receivable->status)" />
                </div>

                <div>
                    <div class="form-label">Tipo</div>
                    <div>{{ $receivable->receivable_type->label() }}</div>
                </div>
                <div>
                    <div class="form-label">Data Prevista</div>
                    <div>{{ $receivable->expected_date->format('d/m/Y') }}</div>
                </div>

                <div>
                    <div class="form-label">Valor Previsto</div>
                    <div class="td-mono">R$ {{ number_format((float) $receivable->expected_amount, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="form-label">Ordem de Serviço</div>
                    <div>{{ $receivable->work_order_number ?? '-' }}</div>
                </div>

                <div style="grid-column:1/-1;">
                    <div class="form-label">Descrição</div>
                    <div>{{ $receivable->description }}</div>
                </div>

                <div style="grid-column:1/-1;">
                    <div class="form-label">Observações</div>
                    <div class="text-muted">{{ $receivable->notes ?: '-' }}</div>
                </div>
            </div>

            @if($receivable->status === ReceivableStatus::RECEIVED)
                <hr class="divider">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div>
                        <div class="form-label">Data de Recebimento</div>
                        <div>{{ $receivable->received_date?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="form-label">Valor Recebido</div>
                        <div class="td-mono">R$ {{ number_format((float) $receivable->received_amount, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="form-label">Forma de Pagamento</div>
                        <div>{{ $receivable->payment_method?->label() ?? '-' }}</div>
                    </div>
                </div>
            @endif

            @if($receivable->attachments->isNotEmpty())
                <hr class="divider">
                <div class="section-header">
                    <h3 class="section-title">Comprovantes</h3>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($receivable->attachments as $attachment)
                        <div style="display:flex;align-items:center;justify-content:space-between;border:1px solid var(--outline-variant);border-radius:var(--radius-sm);padding:10px 12px;">
                            <div>
                                <div style="font-size:13px;font-weight:600;">{{ $attachment->file_name }}</div>
                                <div class="td-muted">
                                    {{ number_format($attachment->size / 1024, 1, ',', '.') }} KB
                                    • {{ $attachment->created_at?->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            <a href="{{ asset('storage/' . $attachment->storage_path) }}" target="_blank" class="btn btn-ghost btn-sm">Abrir</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <div style="display:flex;flex-direction:column;gap:16px;">
            @if($receivable->workOrder)
                <x-card title="Ordem de Serviço Vinculada">
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <div><strong>{{ $receivable->workOrder->number }}</strong> - {{ $receivable->workOrder->customer }}</div>
                        <div class="td-muted">Contratado: <span class="td-mono">R$ {{ number_format((float) $receivable->workOrder->contracted_amount, 2, ',', '.') }}</span></div>
                        <div class="td-muted">Recebido: <span class="td-mono">R$ {{ number_format((float) $receivable->workOrder->received_amount, 2, ',', '.') }}</span></div>
                        <div class="td-muted">Saldo: <span class="td-mono">R$ {{ number_format((float) $receivable->workOrder->receivable_balance, 2, ',', '.') }}</span></div>
                    </div>
                </x-card>
            @endif

            @if($canReceive)
                <x-card title="Marcar como Recebido">
                    <form method="POST" action="{{ route('receivables.mark-received', $receivable) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label class="form-label" for="received_date">Data de Recebimento</label>
                            <input id="received_date" name="received_date" type="date" class="form-control @error('received_date') is-invalid @enderror" value="{{ old('received_date', now()->toDateString()) }}" required>
                            @error('received_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="received_amount">Valor Recebido (R$)</label>
                            <input id="received_amount" name="received_amount" type="number" step="0.01" min="0" class="form-control @error('received_amount') is-invalid @enderror" value="{{ old('received_amount', $receivable->expected_amount) }}" required>
                            @error('received_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="payment_method">Forma de Pagamento</label>
                            <select id="payment_method" name="payment_method" class="form-control form-select @error('payment_method') is-invalid @enderror" required>
                                @foreach($paymentMethods as $paymentMethod)
                                    <option value="{{ $paymentMethod->value }}" @selected(old('payment_method') === $paymentMethod->value)>{{ $paymentMethod->label() }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="receipt_file">Comprovante (opcional)</label>
                            <input id="receipt_file" name="receipt_file" type="file" class="form-control @error('receipt_file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            @error('receipt_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="notes">Observação da baixa</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Confirmar recebimento</button>
                    </form>
                </x-card>
            @endif

            @if($canCancel)
                <x-card title="Cancelar Recebível">
                    <form method="POST" action="{{ route('receivables.cancel', $receivable) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label class="form-label" for="cancel_reason">Motivo (opcional)</label>
                            <textarea id="cancel_reason" name="cancel_reason" rows="3" class="form-control">{{ old('cancel_reason') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;" onclick="return confirm('Confirmar cancelamento deste recebível?');">Cancelar recebível</button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>

    @if($receivable->status === ReceivableStatus::OVERDUE)
        <div class="mt-16">
            <x-error-state title="Recebível em atraso" message="Este lançamento está vencido e exige acompanhamento de cobrança." />
        </div>
    @endif
@endsection

@push('styles')
<style>
@media (max-width: 960px) {
    .receivable-show-layout {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
