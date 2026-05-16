@php
    $payableModel = $payable ?? null;
    $isEditing = $payableModel !== null;
@endphp

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="supplier">Fornecedor</label>
        <input
            id="supplier"
            name="supplier"
            type="text"
            class="form-control @error('supplier') is-invalid @enderror"
            value="{{ old('supplier', $payableModel?->supplier) }}"
            required
        >
        @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="work_order_id">Ordem de Servico</label>
        <select id="work_order_id" name="work_order_id" class="form-control form-select @error('work_order_id') is-invalid @enderror">
            <option value="">Sem vinculo</option>
            @foreach($workOrders as $workOrder)
                <option
                    value="{{ $workOrder->id }}"
                    @selected((string) old('work_order_id', $payableModel?->work_order_id) === (string) $workOrder->id)
                >
                    {{ $workOrder->number }} - {{ $workOrder->customer }}
                </option>
            @endforeach
        </select>
        @error('work_order_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="category">Categoria</label>
        <select id="category" name="category" class="form-control form-select @error('category') is-invalid @enderror" required>
            @foreach($categories as $category)
                <option
                    value="{{ $category->value }}"
                    @selected(old('category', $payableModel?->category?->value) === $category->value)
                >
                    {{ $category->label() }}
                </option>
            @endforeach
        </select>
        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="cost_center">Centro de Custo</label>
        <input
            id="cost_center"
            name="cost_center"
            type="text"
            class="form-control @error('cost_center') is-invalid @enderror"
            value="{{ old('cost_center', $payableModel?->cost_center) }}"
        >
        @error('cost_center')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="issue_date">Data de Emissao</label>
        <input
            id="issue_date"
            name="issue_date"
            type="date"
            class="form-control @error('issue_date') is-invalid @enderror"
            value="{{ old('issue_date', $payableModel?->issue_date?->toDateString() ?? now()->toDateString()) }}"
            required
        >
        @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="due_date">Vencimento da Primeira Parcela</label>
        <input
            id="due_date"
            name="due_date"
            type="date"
            class="form-control @error('due_date') is-invalid @enderror"
            value="{{ old('due_date', $payableModel?->due_date?->toDateString()) }}"
            required
        >
        @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="amount">{{ $isEditing ? 'Valor da Parcela (R$)' : 'Valor Total (R$)' }}</label>
        <input
            id="amount"
            name="amount"
            type="number"
            step="0.01"
            min="0"
            class="form-control @error('amount') is-invalid @enderror"
            value="{{ old('amount', $payableModel?->amount) }}"
            required
        >
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if(!$isEditing)
        <div class="form-group">
            <label class="form-label" for="total_installments">Quantidade de Parcelas</label>
            <input
                id="total_installments"
                name="total_installments"
                type="number"
                min="1"
                max="60"
                class="form-control @error('total_installments') is-invalid @enderror"
                value="{{ old('total_installments', 1) }}"
                required
            >
            @error('total_installments')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @else
        <div class="form-group">
            <label class="form-label" for="payment_method">Forma de Pagamento (padrao)</label>
            <select id="payment_method" name="payment_method" class="form-control form-select @error('payment_method') is-invalid @enderror">
                <option value="">Nao definida</option>
                @foreach($paymentMethods as $paymentMethod)
                    <option value="{{ $paymentMethod->value }}" @selected(old('payment_method', $payableModel?->payment_method?->value) === $paymentMethod->value)>
                        {{ $paymentMethod->label() }}
                    </option>
                @endforeach
            </select>
            @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif
</div>

@if(!$isEditing)
    <div class="form-grid-2">
        <div class="form-group">
            <label class="form-label" for="payment_method">Forma de Pagamento (opcional)</label>
            <select id="payment_method" name="payment_method" class="form-control form-select @error('payment_method') is-invalid @enderror">
                <option value="">Nao definida</option>
                @foreach($paymentMethods as $paymentMethod)
                    <option value="{{ $paymentMethod->value }}" @selected(old('payment_method') === $paymentMethod->value)>
                        {{ $paymentMethod->label() }}
                    </option>
                @endforeach
            </select>
            @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="receipt_file">Anexo (boleto/nota)</label>
            <input id="receipt_file" name="receipt_file" type="file" class="form-control @error('receipt_file') is-invalid @enderror" accept=".pdf,.jpg,.jpeg,.png,.webp">
            @error('receipt_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
@endif

<div class="form-group">
    <label class="form-label" for="description">Descricao</label>
    <input
        id="description"
        name="description"
        type="text"
        class="form-control @error('description') is-invalid @enderror"
        value="{{ old('description', $payableModel?->description) }}"
        required
    >
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label class="form-label" for="notes">Observacoes</label>
    <textarea
        id="notes"
        name="notes"
        rows="4"
        class="form-control @error('notes') is-invalid @enderror"
    >{{ old('notes', $payableModel?->notes) }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div style="display:flex;justify-content:flex-end;gap:8px;">
    <a href="{{ route('payables.index') }}" class="btn btn-ghost">Cancelar</a>
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Salvar' }}</button>
</div>
