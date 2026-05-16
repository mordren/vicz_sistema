@php
    $receivableModel = $receivable ?? null;
@endphp

<div class="form-grid-2">
    <div class="form-group">
        @if(($customers ?? collect())->isNotEmpty())
            <label class="form-label" for="customer_id">Cliente</label>
            <select
                id="customer_id"
                name="customer_id"
                class="form-control form-select @error('customer_id') is-invalid @enderror"
                required
            >
                <option value="">Selecione um cliente...</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id', $receivableModel?->customer_id) == $customer->id)>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
            @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @else
            <label class="form-label" for="customer">Cliente</label>
            <input id="customer" name="customer" type="text" class="form-control @error('customer') is-invalid @enderror" value="{{ old('customer', $receivableModel?->customer) }}" required>
            @error('customer')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @endif
    </div>

    <div class="form-group">
        <label class="form-label" for="work_order_id">Ordem de Serviço</label>
        <select id="work_order_id" name="work_order_id" class="form-control form-select @error('work_order_id') is-invalid @enderror">
            <option value="">Sem vínculo</option>
            @foreach($workOrders as $workOrder)
                <option
                    value="{{ $workOrder->id }}"
                    @selected((string) old('work_order_id', $receivableModel?->work_order_id) === (string) $workOrder->id)
                >
                    {{ $workOrder->number }} - {{ $workOrder->customer ?? $workOrder->customerRef?->name }}
                </option>
            @endforeach
        </select>
        @error('work_order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="receivable_type">Tipo de Recebível</label>
        <select id="receivable_type" name="receivable_type" class="form-control form-select @error('receivable_type') is-invalid @enderror" required>
            @foreach($types as $type)
                <option
                    value="{{ $type->value }}"
                    @selected(old('receivable_type', $receivableModel?->receivable_type?->value) === $type->value)
                >
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        @error('receivable_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="expected_date">Data Prevista</label>
        <input
            id="expected_date"
            name="expected_date"
            type="date"
            class="form-control @error('expected_date') is-invalid @enderror"
            value="{{ old('expected_date', $receivableModel?->expected_date?->toDateString()) }}"
            required
        >
        @error('expected_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="expected_amount">Valor Previsto (R$)</label>
        <input
            id="expected_amount"
            name="expected_amount"
            type="number"
            step="0.01"
            min="0"
            class="form-control @error('expected_amount') is-invalid @enderror"
            value="{{ old('expected_amount', $receivableModel?->expected_amount) }}"
            required
        >
        @error('expected_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="description">Descrição</label>
    <input
        id="description"
        name="description"
        type="text"
        class="form-control @error('description') is-invalid @enderror"
        value="{{ old('description', $receivableModel?->description) }}"
        required
    >
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label class="form-label" for="notes">Observações</label>
    <textarea
        id="notes"
        name="notes"
        rows="4"
        class="form-control @error('notes') is-invalid @enderror"
    >{{ old('notes', $receivableModel?->notes) }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div style="display:flex;justify-content:flex-end;gap:8px;">
    <a href="{{ route('receivables.index') }}" class="btn btn-ghost">Cancelar</a>
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Salvar' }}</button>
</div>
