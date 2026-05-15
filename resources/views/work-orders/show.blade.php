@extends('layouts.app')

@section('title', 'Detalhe da Ordem de Servico - VICZ Implementos')

@section('content')
    @php
        use App\Enums\WorkOrderStatus;
        use App\Support\BadgeHelper;

        $canUpdate = auth()->user()->can('update', $workOrder);
        $canOperational = auth()->user()->can('updateStatus', $workOrder);
        $canDeliver = $canOperational && !in_array($workOrder->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true);
        $canCancel = $canOperational && $workOrder->status !== WorkOrderStatus::DELIVERED;
        $deadline = $workOrder->recalculated_delivery_date ?? $workOrder->expected_delivery_date;
    @endphp

    <x-page-header
        title="{{ $workOrder->number }}"
        subtitle="Visão operacional, financeira e vínculos da ordem"
    >
        @if($canUpdate && !in_array($workOrder->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true))
            <a href="{{ route('work-orders.edit', $workOrder) }}" class="btn btn-secondary">Editar</a>
        @endif
        <a href="{{ route('work-orders.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    @if($isOverdue)
        <x-error-state
            title="Entrega em atraso"
            message="A data de entrega desta ordem está vencida. Priorize atualização operacional e comunicação com o cliente."
        />
    @elseif($isNearDelivery)
        <div class="flash flash-success" role="status" style="margin-bottom:16px;">
            <svg class="flash-icon" viewBox="0 0 24 24"><path d="M19 13H5v-2h14v2z"/></svg>
            Esta ordem está próxima da entrega (até 3 dias). Garanta o fechamento operacional.
        </div>
    @endif

    <div class="work-order-show-layout" style="display:grid;grid-template-columns:1fr 380px;gap:16px;align-items:start;">
        <div style="display:flex;flex-direction:column;gap:16px;">
            <x-card title="Dados Operacionais">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
                    <div>
                        <div class="form-label">Cliente</div>
                        <div>{{ $workOrder->customer }}</div>
                    </div>
                    <div>
                        <div class="form-label">Serviço</div>
                        <div>{{ $workOrder->service_type }}</div>
                    </div>
                    <div>
                        <div class="form-label">Status</div>
                        <x-status-badge :variant="BadgeHelper::variant($workOrder->status)" :label="BadgeHelper::label($workOrder->status)" />
                    </div>

                    <div>
                        <div class="form-label">Responsável</div>
                        <div>{{ $workOrder->responsibleUser?->name ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="form-label">Abertura</div>
                        <div>{{ $workOrder->opened_at?->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <div class="form-label">Prazo final</div>
                        <div>{{ $deadline?->format('d/m/Y') ?? '-' }}</div>
                    </div>

                    <div style="grid-column:1/-1;">
                        <div class="form-label">Andamento</div>
                        <div class="wo-progress-track-lg" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $workOrder->progress_percentage }}">
                            <div class="wo-progress-fill-lg" style="width:{{ $workOrder->progress_percentage }}%;"></div>
                        </div>
                        <div class="td-muted" style="margin-top:6px;">{{ $workOrder->progress_percentage }}% concluído</div>
                    </div>

                    <div style="grid-column:1/-1;">
                        <div class="form-label">Descrição</div>
                        <div>{{ $workOrder->description ?: '-' }}</div>
                    </div>

                    <div style="grid-column:1/-1;">
                        <div class="form-label">Observações</div>
                        <div class="text-muted">{{ $workOrder->notes ?: '-' }}</div>
                    </div>
                </div>
            </x-card>

            <x-card title="Resumo Financeiro">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                    <div>
                        <div class="form-label">Valor contratado</div>
                        <div class="td-mono">R$ {{ number_format((float) $workOrder->contracted_amount, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="form-label">Custo estimado</div>
                        <div class="td-mono">R$ {{ number_format((float) $workOrder->estimated_cost, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="form-label">Custo real</div>
                        <div class="td-mono">R$ {{ number_format((float) $workOrder->actual_cost, 2, ',', '.') }}</div>
                    </div>

                    <div>
                        <div class="form-label">Recebido</div>
                        <div class="td-mono">R$ {{ number_format((float) $workOrder->received_amount, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="form-label">Saldo a receber</div>
                        <div class="td-mono">R$ {{ number_format((float) $workOrder->receivable_balance, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="form-label">Entrada</div>
                        <div class="td-mono">R$ {{ number_format((float) $workOrder->down_payment_amount, 2, ',', '.') }}</div>
                    </div>

                    <div>
                        <div class="form-label">Margem estimada</div>
                        <div class="td-mono {{ $workOrder->estimated_margin >= 0 ? 'wo-positive' : 'wo-negative' }}">
                            R$ {{ number_format((float) $workOrder->estimated_margin, 2, ',', '.') }}
                        </div>
                    </div>
                    <div>
                        <div class="form-label">Margem real</div>
                        <div class="td-mono {{ $workOrder->actual_margin >= 0 ? 'wo-positive' : 'wo-negative' }}">
                            R$ {{ number_format((float) $workOrder->actual_margin, 2, ',', '.') }}
                        </div>
                    </div>
                </div>
            </x-card>

            <x-card title="Contas a Pagar Vinculadas">
                @if($workOrder->payables->isEmpty())
                    <x-empty-state icon="payable" title="Sem contas a pagar vinculadas" message="O custo real será calculado automaticamente quando houver pagamentos baixados nesta OS." />
                @else
                    <div class="table-wrapper" style="border:none;border-radius:0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Fornecedor</th>
                                    <th>Descrição</th>
                                    <th>Vencimento</th>
                                    <th class="text-right">Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workOrder->payables as $payable)
                                    <tr>
                                        <td>{{ $payable->supplier }}</td>
                                        <td>{{ $payable->description }}</td>
                                        <td>{{ $payable->due_date?->format('d/m/Y') }}</td>
                                        <td class="text-right td-mono">R$ {{ number_format((float) ($payable->paid_amount ?? $payable->amount), 2, ',', '.') }}</td>
                                        <td>
                                            <x-status-badge
                                                :variant="\App\Support\BadgeHelper::variant($payable->status)"
                                                :label="\App\Support\BadgeHelper::label($payable->status)"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            <x-card title="Contas a Receber Vinculadas">
                @if($workOrder->receivables->isEmpty())
                    <x-empty-state icon="default" title="Sem contas a receber vinculadas" message="O valor recebido e o saldo da OS serão atualizados automaticamente conforme as baixas." />
                @else
                    <div class="table-wrapper" style="border:none;border-radius:0;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Descrição</th>
                                    <th>Previsão</th>
                                    <th class="text-right">Valor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workOrder->receivables as $receivable)
                                    <tr>
                                        <td>{{ $receivable->customer }}</td>
                                        <td>{{ $receivable->description }}</td>
                                        <td>{{ $receivable->expected_date?->format('d/m/Y') }}</td>
                                        <td class="text-right td-mono">R$ {{ number_format((float) ($receivable->received_amount ?? $receivable->expected_amount), 2, ',', '.') }}</td>
                                        <td>
                                            <x-status-badge
                                                :variant="\App\Support\BadgeHelper::variant($receivable->status)"
                                                :label="\App\Support\BadgeHelper::label($receivable->status)"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            <x-card title="Anexos, fotos e desenhos">
                @if($workOrder->attachments->isEmpty())
                    <x-empty-state icon="report" title="Sem anexos" message="Esta OS ainda não possui arquivos vinculados." />
                @else
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        @foreach($workOrder->attachments as $attachment)
                            <div style="display:flex;align-items:center;justify-content:space-between;border:1px solid var(--outline-variant);border-radius:var(--radius-sm);padding:10px 12px;">
                                <div>
                                    <div style="font-size:13px;font-weight:600;">{{ $attachment->file_name }}</div>
                                    <div class="td-muted">{{ number_format($attachment->size / 1024, 1, ',', '.') }} KB • {{ $attachment->created_at?->format('d/m/Y H:i') }}</div>
                                </div>
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->storage_path) }}" target="_blank" class="btn btn-ghost btn-sm">Abrir</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px;">
            <x-card title="Painel Operacional">
                @if($canOperational)
                    @if(in_array($workOrder->status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true))
                        <x-loading-state :rows="3" message="OS finalizada: sem atualizações operacionais pendentes." />
                    @else
                        <form method="POST" action="{{ route('work-orders.operational-update', $workOrder) }}">
                            @csrf
                            @method('PATCH')

                            <div class="form-group">
                                <label class="form-label" for="status">Status operacional</label>
                                <select id="status" name="status" class="form-control form-select @error('status') is-invalid @enderror" required>
                                    @foreach($statuses as $status)
                                        @if(!in_array($status, [WorkOrderStatus::DELIVERED, WorkOrderStatus::CANCELLED], true))
                                            <option value="{{ $status->value }}" @selected(old('status', $workOrder->status->value) === $status->value)>{{ $status->label() }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="progress_percentage">Progresso (%)</label>
                                <input id="progress_percentage" name="progress_percentage" type="number" min="0" max="100" class="form-control @error('progress_percentage') is-invalid @enderror" value="{{ old('progress_percentage', $workOrder->progress_percentage) }}" required>
                                @error('progress_percentage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="responsible_user_id">Responsável</label>
                                <select id="responsible_user_id" name="responsible_user_id" class="form-control form-select @error('responsible_user_id') is-invalid @enderror">
                                    <option value="">Não definido</option>
                                    @foreach($responsibleUsers as $responsible)
                                        <option value="{{ $responsible->id }}" @selected(old('responsible_user_id', $workOrder->responsible_user_id) == $responsible->id)>{{ $responsible->name }}</option>
                                    @endforeach
                                </select>
                                @error('responsible_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="recalculated_delivery_date">Entrega recalculada</label>
                                <input id="recalculated_delivery_date" name="recalculated_delivery_date" type="date" class="form-control @error('recalculated_delivery_date') is-invalid @enderror" value="{{ old('recalculated_delivery_date', $workOrder->recalculated_delivery_date?->toDateString()) }}">
                                @error('recalculated_delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="notes">Observações operacionais</label>
                                <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $workOrder->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Atualizar painel</button>
                        </form>
                    @endif
                @else
                    <x-error-state title="Acesso restrito" message="Somente o responsável da OS ou administradores podem atualizar o painel operacional." />
                @endif
            </x-card>

            @if($canDeliver)
                <x-card title="Marcar como Entregue">
                    <form method="POST" action="{{ route('work-orders.deliver', $workOrder) }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label class="form-label" for="actual_delivery_date">Data real de entrega</label>
                            <input id="actual_delivery_date" name="actual_delivery_date" type="date" class="form-control @error('actual_delivery_date') is-invalid @enderror" value="{{ old('actual_delivery_date', now()->toDateString()) }}" required>
                            @error('actual_delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="delivery_notes">Observação</label>
                            <textarea id="delivery_notes" name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Confirmar entrega</button>
                    </form>
                </x-card>
            @endif

            @if($canCancel)
                <x-card title="Cancelar Ordem">
                    <form method="POST" action="{{ route('work-orders.cancel', $workOrder) }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label class="form-label" for="cancel_reason">Motivo (opcional)</label>
                            <textarea id="cancel_reason" name="cancel_reason" rows="3" class="form-control">{{ old('cancel_reason') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-danger" style="width:100%;justify-content:center;" onclick="return confirm('Confirmar cancelamento desta ordem?');">Cancelar ordem</button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
@endsection

@push('styles')
<style>
.wo-progress-track-lg {
    width: 100%;
    height: 10px;
    border-radius: var(--radius-full);
    background: var(--surface-container);
    overflow: hidden;
}

.wo-progress-fill-lg {
    height: 100%;
    background: var(--status-info);
}

.wo-positive {
    color: var(--status-success);
}

.wo-negative {
    color: var(--error);
}

@media (max-width: 1120px) {
    .work-order-show-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .work-order-show-layout .card-body > div[style*="grid-template-columns:1fr 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush
