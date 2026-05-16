@php
    $editing = isset($workOrder);
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
                    <option value="{{ $customer->id }}" @selected(old('customer_id', isset($workOrder) ? $workOrder->customer_id : '') == $customer->id)>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
            @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
