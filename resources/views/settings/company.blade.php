@extends('layouts.app')

@section('title', 'Configurações da Empresa')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Configurações da Empresa</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-ghost">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Voltar
        </a>
    </div>

    <div class="card" style="max-width: 920px;">
        <div class="card-body">
        <form method="POST" action="{{ route('settings.company.update') }}" novalidate>
            @csrf
            @method('PATCH')

            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Nome da Empresa</label>
                    <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name', $settings->get('company_name')?->value) }}" required>
                    @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">CNPJ</label>
                    <input type="text" name="company_cnpj" class="form-control @error('company_cnpj') is-invalid @enderror" value="{{ old('company_cnpj', $settings->get('company_cnpj')?->value) }}">
                    @error('company_cnpj')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input type="text" name="company_phone" class="form-control @error('company_phone') is-invalid @enderror" value="{{ old('company_phone', $settings->get('company_phone')?->value) }}">
                    @error('company_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="company_email" class="form-control @error('company_email') is-invalid @enderror" value="{{ old('company_email', $settings->get('company_email')?->value) }}">
                    @error('company_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Endereço</label>
                    <input type="text" name="company_address" class="form-control @error('company_address') is-invalid @enderror" value="{{ old('company_address', $settings->get('company_address')?->value) }}">
                    @error('company_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="company_city" class="form-control @error('company_city') is-invalid @enderror" value="{{ old('company_city', $settings->get('company_city')?->value) }}">
                    @error('company_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Estado (UF)</label>
                    <input type="text" name="company_state" class="form-control @error('company_state') is-invalid @enderror" value="{{ old('company_state', $settings->get('company_state')?->value) }}" maxlength="2">
                    @error('company_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">CEP</label>
                    <input type="text" name="company_zip_code" class="form-control @error('company_zip_code') is-invalid @enderror" value="{{ old('company_zip_code', $settings->get('company_zip_code')?->value) }}">
                    @error('company_zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Moeda Padrão</label>
                    <input type="text" name="default_currency" class="form-control @error('default_currency') is-invalid @enderror" value="{{ old('default_currency', $settings->get('default_currency')?->value ?? 'BRL') }}" maxlength="3" required>
                    @error('default_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" class="form-control @error('timezone') is-invalid @enderror" value="{{ old('timezone', $settings->get('timezone')?->value ?? 'America/Sao_Paulo') }}" required>
                    @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Formato de Data</label>
                    <input type="text" name="date_format" class="form-control @error('date_format') is-invalid @enderror" value="{{ old('date_format', $settings->get('date_format')?->value ?? 'd/m/Y') }}" required>
                    @error('date_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Formato Numérico</label>
                    <input type="text" name="number_format" class="form-control @error('number_format') is-invalid @enderror" value="{{ old('number_format', $settings->get('number_format')?->value ?? 'pt-BR') }}" required>
                    @error('number_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                <a href="{{ route('settings.index') }}" class="btn btn-ghost">Cancelar</a>
            </div>
        </form>
        </div>
    </div>
</div>
@endsection
