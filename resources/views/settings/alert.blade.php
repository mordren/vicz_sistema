@extends('layouts.app')

@section('title', 'Configurações de Alertas')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Configurações de Alertas</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline">Voltar</a>
    </div>

    <div class="card" style="max-width: 920px; border: 1px solid #e9ecef; border-radius: 8px;">
        <form method="POST" action="{{ route('settings.alert.update') }}" novalidate>
            @csrf
            @method('PATCH')

            <div style="display: grid; gap: 16px;">
                <div class="form-group">
                    <input type="hidden" name="alert_overdue_enabled" value="0">
                    <label class="form-checkbox">
                        <input type="checkbox" name="alert_overdue_enabled" value="1" @checked((string) old('alert_overdue_enabled', $settings->get('alert_overdue_enabled')?->value ?? '1') === '1')>
                        Ativar alertas de contas vencidas
                    </label>
                </div>

                <div class="form-group">
                    <input type="hidden" name="alert_proposal_follow_up_enabled" value="0">
                    <label class="form-checkbox">
                        <input type="checkbox" name="alert_proposal_follow_up_enabled" value="1" @checked((string) old('alert_proposal_follow_up_enabled', $settings->get('alert_proposal_follow_up_enabled')?->value ?? '1') === '1')>
                        Ativar alertas de follow-up de propostas
                    </label>
                </div>

                <div class="form-group">
                    <input type="hidden" name="alert_delivery_warning_enabled" value="0">
                    <label class="form-checkbox">
                        <input type="checkbox" name="alert_delivery_warning_enabled" value="1" @checked((string) old('alert_delivery_warning_enabled', $settings->get('alert_delivery_warning_enabled')?->value ?? '1') === '1')>
                        Ativar alertas de entrega
                    </label>
                </div>

                <div class="form-group" style="max-width: 320px;">
                    <label class="form-label">Dias antes do vencimento</label>
                    <input type="number" min="0" name="alert_days_before_due" class="form-control @error('alert_days_before_due') is-invalid @enderror" value="{{ old('alert_days_before_due', $settings->get('alert_days_before_due')?->value ?? 2) }}" required>
                    @error('alert_days_before_due')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                <a href="{{ route('settings.index') }}" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
