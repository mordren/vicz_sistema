@extends('layouts.app')

@section('title', 'Configurações Financeiras')

@section('content')
@php
    $paymentMethods = json_decode($settings->get('default_payment_methods')?->value ?? '[]', true);
    if (!is_array($paymentMethods)) {
        $paymentMethods = [];
    }
@endphp

<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Configurações Financeiras</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-ghost">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Voltar
        </a>
    </div>

    <div class="card" style="max-width: 920px;">
        <div class="card-body">
        <form method="POST" action="{{ route('settings.financial.update') }}" novalidate>
            @csrf
            @method('PATCH')

            <div class="grid-2" style="gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Saldo Inicial de Caixa</label>
                    <input type="number" step="0.01" min="0" name="cash_balance" class="form-control @error('cash_balance') is-invalid @enderror" value="{{ old('cash_balance', $settings->get('cash_balance')?->value ?? 0) }}" required>
                    @error('cash_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Dias de Tolerância para Vencidos</label>
                    <input type="number" min="1" name="overdue_days_threshold" class="form-control @error('overdue_days_threshold') is-invalid @enderror" value="{{ old('overdue_days_threshold', $settings->get('overdue_days_threshold')?->value ?? 5) }}" required>
                    @error('overdue_days_threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Período de Projeção de Caixa (dias)</label>
                    <input type="number" min="7" name="cash_forecast_period" class="form-control @error('cash_forecast_period') is-invalid @enderror" value="{{ old('cash_forecast_period', $settings->get('cash_forecast_period')?->value ?? 30) }}" required>
                    @error('cash_forecast_period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group" style="display: flex; align-items: end;">
                    <div>
                        <input type="hidden" name="include_proposal_scenarios" value="0">
                        <label class="form-checkbox">
                            <input type="checkbox" name="include_proposal_scenarios" value="1" @checked((string) old('include_proposal_scenarios', $settings->get('include_proposal_scenarios')?->value ?? '0') === '1')>
                            Incluir cenários de propostas no forecast
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 8px;">
                <label class="form-label">Métodos de Pagamento Padrão</label>
                <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-top: 8px;">
                    @php
                        $selectedMethods = old('default_payment_methods', $paymentMethods);
                    @endphp
                    @foreach(['PIX', 'Boleto', 'Cartão', 'Transferência', 'Dinheiro', 'Cheque'] as $method)
                        <label class="form-checkbox">
                            <input type="checkbox" name="default_payment_methods[]" value="{{ $method }}" @checked(in_array($method, $selectedMethods, true))>
                            {{ $method }}
                        </label>
                    @endforeach
                </div>
                @error('default_payment_methods')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
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
