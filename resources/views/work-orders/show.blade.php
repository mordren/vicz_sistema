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
                                    <th></th>
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
                                        <td><a href="{{ route('payables.show', $payable) }}" class="btn btn-ghost btn-sm">Abrir</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @can('create', \App\Models\AccountPayable::class)
                    <div style="margin-top:14px;border-top:1px solid var(--outline-variant);padding-top:14px;">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-nova-conta-pagar').style.display='flex';">
                            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor;"><path d="M19 13H13v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            Adicionar pagamento vinculado
                        </button>
                    </div>
                @endcan
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
                                    <th></th>
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
                                        <td><a href="{{ route('receivables.show', $receivable) }}" class="btn btn-ghost btn-sm">Abrir</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @can('create', \App\Models\AccountReceivable::class)
                    <div style="margin-top:14px;border-top:1px solid var(--outline-variant);padding-top:14px;">
                        <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modal-nova-conta-receber').style.display='flex';">
                            <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor;"><path d="M19 13H13v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                            Adicionar recebimento vinculado
                        </button>
                    </div>
                @endcan
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
                                <a href="{{ asset('storage/' . $attachment->storage_path) }}" target="_blank" class="btn btn-ghost btn-sm">Abrir</a>
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
                                <input id="progress_percentage" name="progress_percentage" type="number" min="0" max="100"
                                    maxlength="3"
                                    class="form-control @error('progress_percentage') is-invalid @enderror"
                                    value="{{ old('progress_percentage', $workOrder->progress_percentage) }}"
                                    required
                                    oninput="if(this.value>100)this.value=100;if(this.value<0)this.value=0;">
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

    {{-- Modal: Nova Conta a Pagar --}}
    @can('create', \App\Models\AccountPayable::class)
    <div id="modal-nova-conta-pagar"
         style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(5,20,54,.45);align-items:center;justify-content:center;padding:16px;">
        <div style="background:var(--surface);border-radius:var(--radius-md);padding:24px;width:100%;max-width:560px;box-shadow:var(--shadow-lg);max-height:90vh;overflow-y:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="font-family:var(--font-brand);font-size:16px;font-weight:700;color:var(--primary);margin:0;">Nova Conta a Pagar</h3>
                <button type="button" onclick="closePagar()" style="background:none;border:none;cursor:pointer;padding:4px;">
                    <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--on-surface-variant);"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div id="modal-pagar-error" style="display:none;" class="flash flash-error" role="alert"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mp_supplier">Fornecedor *</label>
                    <input id="mp_supplier" type="text" class="form-control" value="{{ $workOrder->customer }}" maxlength="160" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mp_category">Categoria *</label>
                    <select id="mp_category" class="form-control form-select" required>
                        <option value="">Selecione...</option>
                        @foreach(\App\Enums\PayableCategory::cases() as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;grid-column:1/-1;">
                    <label class="form-label" for="mp_description">Descrição *</label>
                    <input id="mp_description" type="text" class="form-control" placeholder="Ex: Chapas para carroceria OS {{ $workOrder->number }}" maxlength="255" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mp_issue_date">Data de emissão *</label>
                    <input id="mp_issue_date" type="date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mp_due_date">Vencimento *</label>
                    <input id="mp_due_date" type="date" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mp_amount">Valor (R$) *</label>
                    <input id="mp_amount" type="number" step="0.01" min="0.01" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mp_notes">Observações</label>
                    <input id="mp_notes" type="text" class="form-control" maxlength="5000">
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="button" id="mp_submit" class="btn btn-primary" onclick="submitPagar()">Salvar pagamento</button>
                <button type="button" class="btn btn-ghost" onclick="closePagar()">Cancelar</button>
            </div>
        </div>
    </div>
    @endcan

    {{-- Modal: Nova Conta a Receber --}}
    @can('create', \App\Models\AccountReceivable::class)
    <div id="modal-nova-conta-receber"
         style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(5,20,54,.45);align-items:center;justify-content:center;padding:16px;">
        <div style="background:var(--surface);border-radius:var(--radius-md);padding:24px;width:100%;max-width:560px;box-shadow:var(--shadow-lg);max-height:90vh;overflow-y:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="font-family:var(--font-brand);font-size:16px;font-weight:700;color:var(--primary);margin:0;">Novo Recebimento Vinculado</h3>
                <button type="button" onclick="closeReceber()" style="background:none;border:none;cursor:pointer;padding:4px;">
                    <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--on-surface-variant);"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                </button>
            </div>
            <div id="modal-receber-error" style="display:none;" class="flash flash-error" role="alert"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mr_customer">Cliente *</label>
                    <input id="mr_customer" type="text" class="form-control" value="{{ $workOrder->customer }}" maxlength="255" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mr_type">Tipo *</label>
                    <select id="mr_type" class="form-control form-select" required>
                        <option value="">Selecione...</option>
                        @foreach(\App\Enums\ReceivableType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;grid-column:1/-1;">
                    <label class="form-label" for="mr_description">Descrição *</label>
                    <input id="mr_description" type="text" class="form-control" placeholder="Ex: Entrada OS {{ $workOrder->number }}" maxlength="255" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mr_expected_date">Previsão de recebimento *</label>
                    <input id="mr_expected_date" type="date" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" for="mr_expected_amount">Valor esperado (R$) *</label>
                    <input id="mr_expected_amount" type="number" step="0.01" min="0.01" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom:0;grid-column:1/-1;">
                    <label class="form-label" for="mr_notes">Observações</label>
                    <input id="mr_notes" type="text" class="form-control" maxlength="5000">
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="button" id="mr_submit" class="btn btn-primary" onclick="submitReceber()">Salvar recebimento</button>
                <button type="button" class="btn btn-ghost" onclick="closeReceber()">Cancelar</button>
            </div>
        </div>
    </div>
    @endcan

@push('scripts')
<script>
function closePagar() { document.getElementById('modal-nova-conta-pagar').style.display = 'none'; }
function closeReceber() { document.getElementById('modal-nova-conta-receber').style.display = 'none'; }

function showModalError(elId, msg) {
    var el = document.getElementById(elId);
    el.textContent = msg;
    el.style.display = '';
}

function submitPagar() {
    var btn = document.getElementById('mp_submit');
    var errEl = document.getElementById('modal-pagar-error');
    errEl.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    var body = new FormData();
    body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    body.append('work_order_id', '{{ $workOrder->id }}');
    body.append('supplier', document.getElementById('mp_supplier').value);
    body.append('category', document.getElementById('mp_category').value);
    body.append('description', document.getElementById('mp_description').value);
    body.append('issue_date', document.getElementById('mp_issue_date').value);
    body.append('due_date', document.getElementById('mp_due_date').value);
    body.append('amount', document.getElementById('mp_amount').value);
    var notes = document.getElementById('mp_notes').value;
    if (notes) body.append('notes', notes);

    fetch('{{ route('payables.store') }}', { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function(res) {
            if (res.ok) return res.json();
            return res.json().then(function(d) { throw d; });
        })
        .then(function() { window.location.reload(); })
        .catch(function(err) {
            var msg = 'Erro ao salvar. Verifique os dados.';
            if (err && err.errors) msg = Object.values(err.errors).flat().join(' ');
            else if (err && err.message) msg = err.message;
            showModalError('modal-pagar-error', msg);
            btn.disabled = false;
            btn.textContent = 'Salvar pagamento';
        });
}

function submitReceber() {
    var btn = document.getElementById('mr_submit');
    var errEl = document.getElementById('modal-receber-error');
    errEl.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    var body = new FormData();
    body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    body.append('work_order_id', '{{ $workOrder->id }}');
    body.append('customer', document.getElementById('mr_customer').value);
    body.append('receivable_type', document.getElementById('mr_type').value);
    body.append('description', document.getElementById('mr_description').value);
    body.append('expected_date', document.getElementById('mr_expected_date').value);
    body.append('expected_amount', document.getElementById('mr_expected_amount').value);
    var notes = document.getElementById('mr_notes').value;
    if (notes) body.append('notes', notes);

    fetch('{{ route('receivables.store') }}', { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function(res) {
            if (res.ok) return res.json();
            return res.json().then(function(d) { throw d; });
        })
        .then(function() { window.location.reload(); })
        .catch(function(err) {
            var msg = 'Erro ao salvar. Verifique os dados.';
            if (err && err.errors) msg = Object.values(err.errors).flat().join(' ');
            else if (err && err.message) msg = err.message;
            showModalError('modal-receber-error', msg);
            btn.disabled = false;
            btn.textContent = 'Salvar recebimento';
        });
}
</script>
@endpush
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
