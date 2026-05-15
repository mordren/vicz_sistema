@php
    $editing = isset($proposal);
@endphp

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="customer">Cliente</label>
        <input id="customer" name="customer" type="text" class="form-control @error('customer') is-invalid @enderror" value="{{ old('customer', $proposal->customer ?? '') }}" required>
        @error('customer')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="product_or_service_type">Produto/Serviço</label>
        <input id="product_or_service_type" name="product_or_service_type" type="text" class="form-control @error('product_or_service_type') is-invalid @enderror" value="{{ old('product_or_service_type', $proposal->product_or_service_type ?? '') }}" required>
        @error('product_or_service_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="description">Descrição comercial</label>
    <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $proposal->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-grid-3">
    <div class="form-group">
        <label class="form-label" for="amount">Valor (R$)</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $proposal->amount ?? '') }}" required>
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="close_probability">Probabilidade (%)</label>
        <input id="close_probability" name="close_probability" type="number" min="0" max="100" class="form-control @error('close_probability') is-invalid @enderror" value="{{ old('close_probability', $proposal->close_probability ?? 50) }}" required>
        @error('close_probability')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-hint">Valor ponderado é calculado automaticamente.</div>
    </div>

    <div class="form-group">
        <label class="form-label" for="sales_owner_id">Responsável comercial</label>
        <select id="sales_owner_id" name="sales_owner_id" class="form-control form-select @error('sales_owner_id') is-invalid @enderror">
            <option value="">Não definido</option>
            @foreach($salesOwners as $salesOwner)
                <option value="{{ $salesOwner->id }}" @selected(old('sales_owner_id', $proposal->sales_owner_id ?? '') == $salesOwner->id)>{{ $salesOwner->name }}</option>
            @endforeach
        </select>
        @error('sales_owner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="sent_at">Data de envio</label>
        <input id="sent_at" name="sent_at" type="date" class="form-control @error('sent_at') is-invalid @enderror" value="{{ old('sent_at', isset($proposal) ? $proposal->sent_at?->toDateString() : now()->toDateString()) }}">
        @error('sent_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="expected_close_date">Previsão de fechamento</label>
        <input id="expected_close_date" name="expected_close_date" type="date" class="form-control @error('expected_close_date') is-invalid @enderror" value="{{ old('expected_close_date', $proposal->expected_close_date?->toDateString() ?? '') }}">
        @error('expected_close_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="notes">Observações</label>
    <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $proposal->notes ?? '') }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
