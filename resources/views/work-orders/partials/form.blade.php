@php
    $editing = isset($workOrder);
@endphp

<div class="form-grid-2">
    <div class="form-group">
        @if(($customers ?? collect())->isNotEmpty())
            <label class="form-label" for="customer_id">Cliente</label>
            <div style="display:flex;gap:6px;align-items:flex-start;">
                <select
                    id="customer_id"
                    name="customer_id"
                    class="form-control form-select @error('customer_id') is-invalid @enderror"
                    required
                    style="flex:1;"
                >
                    <option value="">Selecione um cliente...</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id', isset($workOrder) ? $workOrder->customer_id : '') == $customer->id)>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
                @can('create', \App\Models\Customer::class)
                    <button type="button" class="btn btn-ghost" style="flex-shrink:0;padding:0 10px;height:38px;" onclick="document.getElementById('modal-new-customer').style.display='flex';" title="Novo cliente">
                        <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;"><path d="M19 13H13v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    </button>
                @endcan
            </div>
            @error('customer_id')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
        @else
            <label class="form-label" for="customer">Cliente</label>
            <input id="customer" name="customer" type="text" class="form-control @error('customer') is-invalid @enderror" value="{{ old('customer', isset($workOrder) ? $workOrder->customer : '') }}" required>
            @error('customer')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>

    <div class="form-group">
        <label class="form-label" for="service_type">Tipo de serviço</label>
        <input
            id="service_type"
            name="service_type"
            type="text"
            class="form-control @error('service_type') is-invalid @enderror"
            value="{{ old('service_type', isset($workOrder) ? $workOrder->service_type : '') }}"
            required
        >
        @error('service_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="description">Descrição técnica</label>
    <textarea
        id="description"
        name="description"
        rows="3"
        class="form-control @error('description') is-invalid @enderror"
    >{{ old('description', isset($workOrder) ? $workOrder->description : '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-grid-3">
    <div class="form-group">
        <label class="form-label" for="responsible_user_id">Responsável</label>
        <select id="responsible_user_id" name="responsible_user_id" class="form-control form-select @error('responsible_user_id') is-invalid @enderror">
            <option value="">Não definido</option>
            @foreach($responsibleUsers as $responsible)
                <option value="{{ $responsible->id }}" @selected(old('responsible_user_id', isset($workOrder) ? $workOrder->responsible_user_id : '') == $responsible->id)>
                    {{ $responsible->name }}
                </option>
            @endforeach
        </select>
        @error('responsible_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="opened_at">Data de abertura</label>
        <input
            id="opened_at"
            name="opened_at"
            type="date"
            class="form-control @error('opened_at') is-invalid @enderror"
            value="{{ old('opened_at', isset($workOrder) ? $workOrder->opened_at?->toDateString() : now()->toDateString()) }}"
            required
        >
        @error('opened_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="expected_delivery_date">Entrega prevista</label>
        <input
            id="expected_delivery_date"
            name="expected_delivery_date"
            type="date"
            class="form-control @error('expected_delivery_date') is-invalid @enderror"
            value="{{ old('expected_delivery_date', isset($workOrder) ? $workOrder->expected_delivery_date?->toDateString() : '') }}"
            required
        >
        @error('expected_delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-3">
    <div class="form-group">
        <label class="form-label" for="recalculated_delivery_date">Entrega recalculada</label>
        <input
            id="recalculated_delivery_date"
            name="recalculated_delivery_date"
            type="date"
            class="form-control @error('recalculated_delivery_date') is-invalid @enderror"
            value="{{ old('recalculated_delivery_date', isset($workOrder) ? $workOrder->recalculated_delivery_date?->toDateString() : '') }}"
        >
        @error('recalculated_delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="contracted_amount">Valor contratado (R$)</label>
        <input
            id="contracted_amount"
            name="contracted_amount"
            type="number"
            step="0.01"
            min="0"
            class="form-control @error('contracted_amount') is-invalid @enderror"
            value="{{ old('contracted_amount', isset($workOrder) ? $workOrder->contracted_amount : '') }}"
            required
        >
        @error('contracted_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="down_payment_amount">Entrada (R$)</label>
        <input
            id="down_payment_amount"
            name="down_payment_amount"
            type="number"
            step="0.01"
            min="0"
            class="form-control @error('down_payment_amount') is-invalid @enderror"
            value="{{ old('down_payment_amount', isset($workOrder) ? $workOrder->down_payment_amount : 0) }}"
        >
        @error('down_payment_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="estimated_cost">Custo estimado (R$)</label>
        <input
            id="estimated_cost"
            name="estimated_cost"
            type="number"
            step="0.01"
            min="0"
            class="form-control @error('estimated_cost') is-invalid @enderror"
            value="{{ old('estimated_cost', isset($workOrder) ? $workOrder->estimated_cost : '') }}"
            required
        >
        @error('estimated_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="notes">Observações</label>
        <textarea
            id="notes"
            name="notes"
            rows="3"
            class="form-control @error('notes') is-invalid @enderror"
        >{{ old('notes', isset($workOrder) ? $workOrder->notes : '') }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@can('create', \App\Models\Customer::class)
@if(($customers ?? collect())->isNotEmpty())
{{-- Modal: Novo cliente rápido --}}
<div id="modal-new-customer"
     style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(5,20,54,.45);align-items:center;justify-content:center;padding:16px;">
    <div style="background:var(--surface);border-radius:var(--radius-md);padding:24px;width:100%;max-width:480px;box-shadow:var(--shadow-lg);max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="font-family:var(--font-brand);font-size:16px;font-weight:700;color:var(--primary);margin:0;">Novo Cliente</h3>
            <button type="button" onclick="document.getElementById('modal-new-customer').style.display='none';" style="background:none;border:none;cursor:pointer;padding:4px;">
                <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--on-surface-variant);"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </button>
        </div>
        <div id="modal-new-customer-error" style="display:none;" class="flash flash-error" role="alert"></div>
        <div class="form-group">
            <label class="form-label" for="nc_type">Tipo *</label>
            <select id="nc_type" class="form-control form-select" onchange="ncToggleFields()">
                <option value="INDIVIDUAL">Pessoa Física</option>
                <option value="COMPANY">Pessoa Jurídica</option>
            </select>
        </div>
        <div id="nc_pf_fields">
            <div class="form-group">
                <label class="form-label" for="nc_name">Nome *</label>
                <input id="nc_name" type="text" class="form-control" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label" for="nc_cpf">CPF *</label>
                <input id="nc_cpf" type="text" class="form-control" maxlength="14" placeholder="000.000.000-00" inputmode="numeric">
            </div>
        </div>
        <div id="nc_pj_fields" style="display:none;">
            <div class="form-group">
                <label class="form-label" for="nc_legal_name">Razão Social *</label>
                <input id="nc_legal_name" type="text" class="form-control" maxlength="255">
            </div>
            <div class="form-group">
                <label class="form-label" for="nc_cnpj">CNPJ *</label>
                <input id="nc_cnpj" type="text" class="form-control" maxlength="18" placeholder="00.000.000/0000-00" inputmode="numeric">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="nc_phone">Telefone</label>
            <input id="nc_phone" type="text" class="form-control" maxlength="15" placeholder="(00) 00000-0000" inputmode="numeric">
        </div>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button type="button" id="nc_submit" class="btn btn-primary" onclick="ncSubmit()">Salvar cliente</button>
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('modal-new-customer').style.display='none';">Cancelar</button>
        </div>
    </div>
</div>
<script>
function ncMaskCpf(v) {
    v = v.replace(/\D/g, '').substring(0, 11);
    if (v.length > 9) return v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    if (v.length > 6) return v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    if (v.length > 3) return v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    return v;
}
function ncMaskCnpj(v) {
    v = v.replace(/\D/g, '').substring(0, 14);
    if (v.length > 12) return v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2})/, '$1.$2.$3/$4-$5');
    if (v.length > 8)  return v.replace(/(\d{2})(\d{3})(\d{3})(\d{1,4})/, '$1.$2.$3/$4');
    if (v.length > 5)  return v.replace(/(\d{2})(\d{3})(\d{1,3})/, '$1.$2.$3');
    if (v.length > 2)  return v.replace(/(\d{2})(\d{1,3})/, '$1.$2');
    return v;
}
function ncMaskPhone(v) {
    v = v.replace(/\D/g, '').substring(0, 11);
    if (v.length > 10) return v.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    if (v.length > 6)  return v.replace(/(\d{2})(\d{4,5})(\d{0,4})/, '($1) $2-$3');
    if (v.length > 2)  return v.replace(/(\d{2})(\d{1,5})/, '($1) $2');
    return v;
}
document.addEventListener('DOMContentLoaded', function () {
    var cpfEl   = document.getElementById('nc_cpf');
    var cnpjEl  = document.getElementById('nc_cnpj');
    var phoneEl = document.getElementById('nc_phone');
    if (cpfEl)   cpfEl.addEventListener('input',   function () { var c = this.selectionStart; this.value = ncMaskCpf(this.value);   });
    if (cnpjEl)  cnpjEl.addEventListener('input',  function () { var c = this.selectionStart; this.value = ncMaskCnpj(this.value);  });
    if (phoneEl) phoneEl.addEventListener('input', function () { var c = this.selectionStart; this.value = ncMaskPhone(this.value); });
});
function ncToggleFields() {
    var t = document.getElementById('nc_type').value;
    document.getElementById('nc_pf_fields').style.display = t === 'INDIVIDUAL' ? '' : 'none';
    document.getElementById('nc_pj_fields').style.display = t === 'COMPANY' ? '' : 'none';
}
function ncSubmit() {
    var btn = document.getElementById('nc_submit');
    var errEl = document.getElementById('modal-new-customer-error');
    errEl.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    var type = document.getElementById('nc_type').value;
    var body = new FormData();
    body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    body.append('type', type);
    body.append('status', 'ACTIVE');
    if (type === 'INDIVIDUAL') {
        body.append('name', document.getElementById('nc_name').value);
        body.append('cpf', document.getElementById('nc_cpf').value.replace(/\D/g, ''));
    } else {
        body.append('legal_name', document.getElementById('nc_legal_name').value);
        body.append('cnpj', document.getElementById('nc_cnpj').value.replace(/\D/g, ''));
    }
    var phone = document.getElementById('nc_phone').value.replace(/\D/g, '');
    if (phone) body.append('phone', phone);

    fetch('{{ route('customers.store') }}', { method: 'POST', body: body, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function(res) {
            if (res.ok) {
                return res.json();
            }
            return res.json().then(function(data) { throw data; });
        })
        .then(function(data) {
            if (data && data.id && data.name) {
                var sel = document.getElementById('customer_id');
                var opt = new Option(data.name, data.id, true, true);
                sel.appendChild(opt);
                sel.value = data.id;
                document.getElementById('modal-new-customer').style.display = 'none';
                // Clear modal fields
                document.getElementById('nc_name').value = '';
                document.getElementById('nc_cpf').value = '';
                document.getElementById('nc_legal_name').value = '';
                document.getElementById('nc_cnpj').value = '';
                document.getElementById('nc_phone').value = '';
            } else {
                window.location.reload();
            }
        })
        .catch(function(err) {
            var msg = 'Erro ao salvar. Verifique os dados.';
            if (err && err.errors) {
                msg = Object.values(err.errors).flat().join(' ');
            } else if (err && err.message) {
                msg = err.message;
            }
            errEl.textContent = msg;
            errEl.style.display = '';
            btn.disabled = false;
            btn.textContent = 'Salvar cliente';
        });
}
</script>
@endif
@endcan
