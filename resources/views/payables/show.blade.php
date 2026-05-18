@extends('layouts.app')

@section('title', 'Detalhe da Conta a Pagar - VICZ Implementos')

@section('content')
    @php
        use App\Enums\PayableStatus;
        use App\Support\BadgeHelper;

        $canEdit = auth()->user()->can('update', $payable)
            && in_array($payable->status, [PayableStatus::OPEN, PayableStatus::OVERDUE], true);

        $canMarkPaid = auth()->user()->can('markAsPaid', $payable)
            && in_array($payable->status, [PayableStatus::OPEN, PayableStatus::OVERDUE], true);

        $canCancel = auth()->user()->can('update', $payable)
            && in_array($payable->status, [PayableStatus::OPEN, PayableStatus::OVERDUE], true);
    @endphp

    <x-page-header
        title="Conta #{{ $payable->id }}"
        subtitle="Detalhes, baixa e auditoria do lancamento"
    >
        @if($canEdit)
            <a href="{{ route('payables.edit', $payable) }}" class="btn btn-secondary">Editar</a>
        @endif
        <a href="{{ route('payables.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    <div class="payable-show-layout" style="display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start;">
        <x-card title="Dados da Conta">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div>
                    <div class="form-label">Fornecedor</div>
                    <div>{{ $payable->supplier }}</div>
                </div>
                <div>
                    <div class="form-label">Status</div>
                    <x-status-badge :variant="BadgeHelper::variant($payable->status)" :label="BadgeHelper::label($payable->status)" />
                </div>

                <div>
                    <div class="form-label">Categoria</div>
                    <div>{{ $payable->category->label() }}</div>
                </div>
                <div>
                    <div class="form-label">Parcela</div>
                    <div>{{ $payable->installment_number }}/{{ $payable->total_installments }}</div>
                </div>

                <div>
                    <div class="form-label">Data de Emissao</div>
                    <div>{{ $payable->issue_date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="form-label">Vencimento</div>
                    <div>{{ $payable->due_date->format('d/m/Y') }}</div>
                </div>

                <div>
                    <div class="form-label">Valor da Parcela</div>
                    <div class="td-mono">R$ {{ number_format((float) $payable->amount, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="form-label">Centro de Custo</div>
                    <div>{{ $payable->cost_center ?: '-' }}</div>
                </div>

                <div style="grid-column:1/-1;">
                    <div class="form-label">Descricao</div>
                    <div>{{ $payable->description }}</div>
                </div>

                <div style="grid-column:1/-1;">
                    <div class="form-label">Observacoes</div>
                    <div class="text-muted">{{ $payable->notes ?: '-' }}</div>
                </div>
            </div>

            @if($payable->status === PayableStatus::PAID)
                <hr class="divider">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div>
                        <div class="form-label">Data de Pagamento</div>
                        <div>{{ $payable->payment_date?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="form-label">Valor Pago</div>
                        <div class="td-mono">R$ {{ number_format((float) $payable->paid_amount, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="form-label">Forma de Pagamento</div>
                        <div>{{ $payable->payment_method?->label() ?? '-' }}</div>
                    </div>
                </div>
            @endif

            @if($payable->attachments->isNotEmpty())
                <hr class="divider">
                <div class="section-header">
                    <h3 class="section-title">Anexos</h3>
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    @foreach($payable->attachments as $attachment)
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
            @if($payable->workOrder)
                <x-card title="Ordem de Servico Vinculada">
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <div><strong>{{ $payable->workOrder->number }}</strong> - {{ $payable->workOrder->customer }}</div>
                        <div class="td-muted">Custo estimado: <span class="td-mono">R$ {{ number_format((float) $payable->workOrder->estimated_cost, 2, ',', '.') }}</span></div>
                        <div class="td-muted">Custo real: <span class="td-mono">R$ {{ number_format((float) $payable->workOrder->actual_cost, 2, ',', '.') }}</span></div>
                        <div class="td-muted">Margem estimada: <span class="td-mono">R$ {{ number_format((float) $payable->workOrder->estimated_margin, 2, ',', '.') }}</span></div>
                        <div class="td-muted">Margem real: <span class="td-mono">R$ {{ number_format((float) $payable->workOrder->actual_margin, 2, ',', '.') }}</span></div>
                    </div>
                </x-card>
            @endif

            @if($canMarkPaid)
                <x-card title="Marcar como Paga">
                    <form method="POST" action="{{ route('payables.mark-paid', $payable) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label class="form-label" for="payment_date">Data de Pagamento</label>
                            <input id="payment_date" name="payment_date" type="date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->toDateString()) }}" required>
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="paid_amount">Valor Pago (R$)</label>
                            <input id="paid_amount" name="paid_amount" type="number" step="0.01" min="0" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', $payable->amount) }}" required>
                            @error('paid_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <label class="form-label" for="notes">Observacao da baixa</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Confirmar pagamento</button>
                    </form>
                </x-card>
            @endif

            @if($canCancel)
                <x-card title="Cancelar Conta">
                    <form method="POST" action="{{ route('payables.cancel', $payable) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label class="form-label" for="cancel_reason">Motivo (opcional)</label>
                            <textarea id="cancel_reason" name="cancel_reason" rows="3" class="form-control">{{ old('cancel_reason') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;" onclick="return confirm('Confirmar cancelamento desta conta?');">Cancelar conta</button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>

    @if($payable->status === PayableStatus::OVERDUE)
        <div class="mt-16">
            <x-error-state title="Conta vencida" message="Este lancamento esta vencido e exige pagamento prioritario." />
        </div>
    @endif
@endsection

@push('styles')
<style>
@media (max-width: 960px) {
    .payable-show-layout {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush
