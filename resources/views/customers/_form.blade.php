@php
    use App\Enums\CustomerType;

    $customerModel = $customer ?? null;
    $selectedType = old('type', $customerModel?->type?->value ?? CustomerType::INDIVIDUAL->value);
@endphp

<div class="form-grid-2" data-customer-section="top">
    <div class="form-group">
        <label class="form-label" for="type">Tipo</label>
        <select id="type" name="type" class="form-control form-select @error('type') is-invalid @enderror" required>
            @foreach($types as $type)
                <option value="{{ $type->value }}" @selected($selectedType === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="status">Status</label>
        <select id="status" name="status" class="form-control form-select @error('status') is-invalid @enderror" required>
            @foreach($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $customerModel?->status?->value ?? \App\Enums\CustomerStatus::ACTIVE->value) === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group" data-customer-field="INDIVIDUAL">
        <label class="form-label" for="name">Nome</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customerModel?->name) }}" data-required-for="INDIVIDUAL">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group" data-customer-field="COMPANY" hidden>
        <label class="form-label" for="legal_name">Razao social</label>
        <input id="legal_name" name="legal_name" type="text" class="form-control @error('legal_name') is-invalid @enderror" value="{{ old('legal_name', $customerModel?->legal_name) }}" data-required-for="COMPANY">
        @error('legal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-3">
    <div class="form-group" data-customer-field="INDIVIDUAL">
        <label class="form-label" for="cpf">CPF</label>
        <input id="cpf" name="cpf" type="text" class="form-control @error('cpf') is-invalid @enderror" value="{{ old('cpf', $customerModel?->cpf) }}" inputmode="numeric" autocomplete="off" data-mask="cpf" data-required-for="INDIVIDUAL">
        @error('cpf')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group" data-customer-field="COMPANY" hidden>
        <label class="form-label" for="cnpj">CNPJ</label>
        <input id="cnpj" name="cnpj" type="text" class="form-control @error('cnpj') is-invalid @enderror" value="{{ old('cnpj', $customerModel?->cnpj) }}" inputmode="numeric" autocomplete="off" data-mask="cnpj" data-required-for="COMPANY">
        @error('cnpj')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="credit_limit">Limite de credito (R$)</label>
        <input id="credit_limit" name="credit_limit" type="number" step="0.01" min="0" class="form-control @error('credit_limit') is-invalid @enderror" value="{{ old('credit_limit', $customerModel?->credit_limit) }}">
        @error('credit_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customerModel?->email) }}">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-3">
    <div class="form-group">
        <label class="form-label" for="phone">Telefone</label>
        <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $customerModel?->phone) }}" inputmode="numeric" autocomplete="off" data-mask="phone">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="whatsapp">WhatsApp</label>
        <input id="whatsapp" name="whatsapp" type="text" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp', $customerModel?->whatsapp) }}" inputmode="numeric" autocomplete="off" data-mask="phone">
        @error('whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="secondary_phone">Telefone secundario</label>
        <input id="secondary_phone" name="secondary_phone" type="text" class="form-control @error('secondary_phone') is-invalid @enderror" value="{{ old('secondary_phone', $customerModel?->secondary_phone) }}" inputmode="numeric" autocomplete="off" data-mask="phone">
        @error('secondary_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-3">
    <div class="form-group">
        <label class="form-label" for="zip_code">CEP</label>
        <input id="zip_code" name="zip_code" type="text" class="form-control @error('zip_code') is-invalid @enderror" value="{{ old('zip_code', $customerModel?->zip_code) }}" inputmode="numeric" autocomplete="postal-code" data-mask="zip" data-cep-field>
        @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="city">Cidade</label>
        <input id="city" name="city" type="text" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $customerModel?->city) }}" data-cep-target="city">
        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label class="form-label" for="state">Estado</label>
        <input id="state" name="state" type="text" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $customerModel?->state) }}" data-cep-target="state">
        @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-grid-2">
    <div class="form-group">
        <label class="form-label" for="street">Rua</label>
        <input id="street" name="street" type="text" class="form-control @error('street') is-invalid @enderror" value="{{ old('street', $customerModel?->street) }}" data-cep-target="street">
        @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-grid-3">
        <div class="form-group">
            <label class="form-label" for="number">Numero</label>
            <input id="number" name="number" type="text" class="form-control @error('number') is-invalid @enderror" value="{{ old('number', $customerModel?->number) }}">
            @error('number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="neighborhood">Bairro</label>
            <input id="neighborhood" name="neighborhood" type="text" class="form-control @error('neighborhood') is-invalid @enderror" value="{{ old('neighborhood', $customerModel?->neighborhood) }}" data-cep-target="neighborhood">
            @error('neighborhood')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label class="form-label" for="complement">Complemento</label>
            <input id="complement" name="complement" type="text" class="form-control @error('complement') is-invalid @enderror" value="{{ old('complement', $customerModel?->complement) }}" data-cep-target="complement">
            @error('complement')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label class="form-label" for="notes">Observacoes</label>
    <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $customerModel?->notes) }}</textarea>
    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div style="display:flex;justify-content:flex-end;gap:8px;">
    <a href="{{ route('customers.index') }}" class="btn btn-ghost">Cancelar</a>
    <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Salvar' }}</button>
</div>

@push('scripts')
<script>
(function () {
    const form = document.querySelector('[data-customer-form]') || document.querySelector('form[action*="customers"]');

    if (!form) {
        return;
    }

    const typeSelect = form.querySelector('#type');
    const nameInput = form.querySelector('#name');
    const legalNameInput = form.querySelector('#legal_name');
    const cpfInput = form.querySelector('#cpf');
    const cpfConfirmationInput = form.querySelector('#cpf_confirmation');
    const cnpjInput = form.querySelector('#cnpj');
    const zipCodeInput = form.querySelector('#zip_code');
    const streetInput = form.querySelector('#street');
    const neighborhoodInput = form.querySelector('#neighborhood');
    const cityInput = form.querySelector('#city');
    const stateInput = form.querySelector('#state');
    const complementInput = form.querySelector('#complement');

    const groups = Array.from(form.querySelectorAll('[data-customer-field]'));
    const masks = Array.from(form.querySelectorAll('[data-mask]'));

    const digitsOnly = (value) => String(value || '').replace(/\D+/g, '');

    const formatCpf = (value) => {
        const digits = digitsOnly(value).slice(0, 11);
        return digits
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
    };

    const formatCnpj = (value) => {
        const digits = digitsOnly(value).slice(0, 14);
        return digits
            .replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/^(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4')
            .replace(/^(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d)/, '$1.$2.$3/$4-$5');
    };

    const formatPhone = (value) => {
        const digits = digitsOnly(value).slice(0, 11);

        if (digits.length <= 10) {
            return digits
                .replace(/^(\d{2})(\d)/, '($1) $2')
                .replace(/^(\(\d{2}\) \d{4})(\d)/, '$1-$2');
        }

        return digits
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/^(\(\d{2}\) \d{5})(\d)/, '$1-$2');
    };

    const formatZip = (value) => {
        const digits = digitsOnly(value).slice(0, 8);
        return digits.replace(/^(\d{5})(\d)/, '$1-$2');
    };

    const normalizeTarget = (target, value) => {
        if (target === 'cpf') {
            return formatCpf(value);
        }

        if (target === 'cnpj') {
            return formatCnpj(value);
        }

        if (target === 'phone') {
            return formatPhone(value);
        }

        if (target === 'zip') {
            return formatZip(value);
        }

        return value;
    };

    masks.forEach((input) => {
        input.value = normalizeTarget(input.dataset.mask, input.value);
    });

    const setGroupState = () => {
        const selectedType = typeSelect.value;

        groups.forEach((group) => {
            const visible = group.dataset.customerField === selectedType;
            group.hidden = !visible;

            group.querySelectorAll('input, select, textarea').forEach((control) => {
                const requiredFor = control.dataset.requiredFor;
                control.disabled = !visible;
                control.required = visible && requiredFor === selectedType;

                if (!visible) {
                    control.setCustomValidity('');
                }
            });
        });

        if (selectedType === 'COMPANY' && legalNameInput && nameInput) {
            nameInput.value = legalNameInput.value;
        }
    };

    masks.forEach((input) => {
        const target = input.dataset.mask;

        input.addEventListener('input', () => {
            input.value = normalizeTarget(target, input.value);
            input.setCustomValidity('');

            if (input === legalNameInput && typeSelect.value === 'COMPANY' && nameInput) {
                nameInput.value = legalNameInput.value;
            }
        });
    });

    if (typeSelect) {
        typeSelect.addEventListener('change', setGroupState);
    }

    if (legalNameInput && nameInput) {
        legalNameInput.addEventListener('input', () => {
            if (typeSelect.value === 'COMPANY') {
                nameInput.value = legalNameInput.value;
            }
        });
    }

    if (zipCodeInput) {
        const fillAddress = async () => {
            const cep = digitsOnly(zipCodeInput.value);

            zipCodeInput.setCustomValidity('');

            if (cep.length !== 8) {
                zipCodeInput.setCustomValidity('CEP deve conter 8 digitos.');
                zipCodeInput.reportValidity();
                return;
            }

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);

                if (!response.ok) {
                    throw new Error('CEP nao encontrado.');
                }

                const data = await response.json();

                if (data.erro) {
                    throw new Error('CEP nao encontrado.');
                }

                if (streetInput && data.logradouro) {
                    streetInput.value = data.logradouro;
                }

                if (neighborhoodInput && data.bairro) {
                    neighborhoodInput.value = data.bairro;
                }

                if (cityInput && data.localidade) {
                    cityInput.value = data.localidade;
                }

                if (stateInput && data.uf) {
                    stateInput.value = data.uf;
                }

                if (complementInput && data.complemento) {
                    complementInput.value = data.complemento;
                }
            } catch (error) {
                zipCodeInput.setCustomValidity('Nao foi possivel localizar o CEP informado.');
                zipCodeInput.reportValidity();
            }
        };

        zipCodeInput.addEventListener('blur', fillAddress);
        zipCodeInput.addEventListener('input', () => {
            zipCodeInput.value = formatZip(zipCodeInput.value);
            zipCodeInput.setCustomValidity('');
        });
    }

    setGroupState();
})();
</script>
@endpush
