@extends('layouts.app')

@section('title', 'Detalhe da Proposta - VICZ Implementos')

@section('content')
    @php
        use App\Enums\ProposalStatus;
        use App\Support\BadgeHelper;

        $canUpdate = auth()->user()->can('update', $proposal)
            && !in_array($proposal->status, [ProposalStatus::LOST, ProposalStatus::CANCELLED], true);
        $canApprove = auth()->user()->can('approve', $proposal)
            && in_array($proposal->status, [ProposalStatus::SENT, ProposalStatus::NEGOTIATING], true);
        $canMarkLost = auth()->user()->can('markAsLost', $proposal)
            && in_array($proposal->status, [ProposalStatus::SENT, ProposalStatus::NEGOTIATING], true);
        $canCancel = auth()->user()->can('cancel', $proposal)
            && in_array($proposal->status, [ProposalStatus::SENT, ProposalStatus::NEGOTIATING], true);
        $canDelete = auth()->user()->can('delete', $proposal);
    @endphp

    <x-page-header
        title="{{ $proposal->number }}"
        subtitle="Detalhamento comercial e fluxo de conversão"
    >
        @if($canUpdate)
            <a href="{{ route('proposals.edit', $proposal) }}" class="btn btn-secondary">Editar</a>
        @endif
        @if($canDelete)
            <form method="POST" action="{{ route('proposals.destroy', $proposal) }}" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Excluir esta proposta permanentemente? Esta ação não pode ser desfeita.')">Excluir</button>
            </form>
        @endif
        <a href="{{ route('proposals.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    @if($proposal->status === ProposalStatus::LOST)
        <x-error-state
            title="Proposta perdida"
            message="Este registro foi perdido e não compõe o forecast ponderado."
        />
    @elseif($proposal->status === ProposalStatus::CANCELLED)
        <x-error-state
            title="Proposta cancelada"
            message="Este registro foi cancelado e não compõe o forecast comercial."
        />
    @endif

    <div class="proposal-show-layout layout-aside" style="gap:16px;align-items:start;">
        <div style="display:flex;flex-direction:column;gap:16px;">
            <x-card title="Dados da Proposta">
                <div class="grid-3" style="gap:14px;">
                    <div>
                        <div class="form-label">Cliente</div>
                        <div>{{ $proposal->customer }}</div>
                    </div>
                    <div>
                        <div class="form-label">Produto/Serviço</div>
                        <div>{{ $proposal->product_or_service_type }}</div>
                    </div>
                    <div>
                        <div class="form-label">Status</div>
                        <x-status-badge :variant="BadgeHelper::variant($proposal->status)" :label="BadgeHelper::label($proposal->status)" />
                    </div>

                    <div>
                        <div class="form-label">Responsável comercial</div>
                        <div>{{ $proposal->salesOwner?->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="form-label">Data de envio</div>
                        <div>{{ $proposal->sent_at?->format('d/m/Y') ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="form-label">Fechamento esperado</div>
                        <div>{{ $proposal->expected_close_date?->format('d/m/Y') ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="form-label">Valor</div>
                        <div class="td-mono">R$ {{ number_format((float) $proposal->amount, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="form-label">Probabilidade</div>
                        <div class="proposal-probability-wrap">
                            <div class="proposal-probability-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $proposal->close_probability }}">
                                <div class="proposal-probability-fill" style="width:{{ $proposal->close_probability }}%;"></div>
                            </div>
                            <span class="proposal-probability-label">{{ $proposal->close_probability }}%</span>
                        </div>
                    </div>
                    <div>
                        <div class="form-label">Valor ponderado</div>
                        <div class="td-mono">R$ {{ number_format((float) $proposal->weighted_amount, 2, ',', '.') }}</div>
                    </div>

                    <div style="grid-column:1/-1;">
                        <div class="form-label">Descrição</div>
                        <div>{{ $proposal->description ?: '-' }}</div>
                    </div>

                    <div style="grid-column:1/-1;">
                        <div class="form-label">Observações</div>
                        <div class="text-muted">{{ $proposal->notes ?: '-' }}</div>
                    </div>

                    @if($proposal->status === ProposalStatus::LOST)
                        <div style="grid-column:1/-1;">
                            <div class="form-label">Motivo da perda</div>
                            <div class="text-muted">{{ $proposal->loss_reason ?: '-' }}</div>
                        </div>
                    @endif
                </div>
            </x-card>

            <x-card title="Anexos">
                @if($proposal->attachments->isEmpty())
                    <x-empty-state icon="report" title="Sem anexos" message="Esta proposta ainda não possui arquivos vinculados." />
                @else
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        @foreach($proposal->attachments as $attachment)
                            <div style="display:flex;align-items:center;justify-content:space-between;border:1px solid var(--outline-variant);border-radius:var(--radius-sm);padding:10px 12px;">
                                <div>
                                    <div style="font-size:13px;font-weight:600;">{{ $attachment->file_name }}</div>
                                    <div class="td-muted">{{ number_format($attachment->size / 1024, 1, ',', '.') }} KB • {{ $attachment->created_at?->format('d/m/Y H:i') }}</div>
                                </div>
                                <a href="{{ asset('storage/' . $attachment->storage_path) }}" target="_blank" class="btn btn-ghost btn-sm">Abrir</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px;">
            @if($canApprove)
                <x-card title="Aprovar Proposta">
                    <form method="POST" action="{{ route('proposals.approve', $proposal) }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label class="form-label" for="approve_notes">Observação</label>
                            <textarea id="approve_notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Aprovar proposta</button>
                    </form>
                </x-card>
            @endif

            @if($canMarkLost)
                <x-card title="Marcar como Perdida">
                    <form method="POST" action="{{ route('proposals.mark-lost', $proposal) }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label class="form-label" for="loss_reason">Motivo da perda</label>
                            <textarea id="loss_reason" name="loss_reason" rows="3" class="form-control @error('loss_reason') is-invalid @enderror" required>{{ old('loss_reason') }}</textarea>
                            @error('loss_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="lost_notes">Observação</label>
                            <textarea id="lost_notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;">Marcar como perdida</button>
                    </form>
                </x-card>
            @endif

            @if($canCancel)
                <x-card title="Cancelar Proposta">
                    <form method="POST" action="{{ route('proposals.cancel', $proposal) }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label class="form-label" for="cancel_notes">Motivo/observação</label>
                            <textarea id="cancel_notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;" onclick="return confirm('Confirmar cancelamento desta proposta?');">Cancelar proposta</button>
                    </form>
                </x-card>
            @endif

            <x-card title="Conversão para Ordem de Serviço">
                @if($proposal->workOrder)
                    <div class="text-muted" style="margin-bottom:10px;">Esta proposta já foi convertida.</div>
                    <a href="{{ route('work-orders.show', $proposal->workOrder) }}" class="btn btn-secondary" style="width:100%;justify-content:center;">Ver {{ $proposal->workOrder->number }}</a>
                @elseif($canConvert)
                    <form method="POST" action="{{ route('proposals.convert-to-work-order', $proposal) }}">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="create_down_payment_receivable">Recebível inicial</label>
                            <select id="create_down_payment_receivable" name="create_down_payment_receivable" class="form-control form-select">
                                <option value="0" @selected(old('create_down_payment_receivable', '0') === '0')>Não criar</option>
                                <option value="1" @selected(old('create_down_payment_receivable') === '1')>Criar recebível de entrada</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="down_payment_amount">Valor da entrada (R$)</label>
                            <input id="down_payment_amount" name="down_payment_amount" type="number" step="0.01" min="0" class="form-control" value="{{ old('down_payment_amount') }}">
                            <div class="form-hint">Use este campo apenas se escolher criar o recebível inicial.</div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Converter para OS</button>
                    </form>
                @else
                    <x-loading-state :rows="3" message="Conversão disponível somente para propostas aprovadas." />
                @endif
            </x-card>
        </div>
    </div>
@endsection

@push('styles')
<style>
.proposal-probability-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}

.proposal-probability-track {
    width: 120px;
    height: 10px;
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

</style>
@endpush
