@extends('layouts.app')

@section('title', 'Configurações de Alertas')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Configurações de Alertas</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-ghost">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Voltar
        </a>
    </div>

    <div class="card" style="max-width: 920px;">
        <div class="card-body">
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

                <div class="form-group" style="max-width: 520px;">
                    <label class="form-label">Emails para resumo automatico</label>
                    <input
                        type="text"
                        name="alert_email_recipients"
                        class="form-control @error('alert_email_recipients') is-invalid @enderror"
                        value="{{ old('alert_email_recipients', $settings->get('alert_email_recipients')?->value ?? '') }}"
                        placeholder="financeiro@empresa.com, gestor@empresa.com"
                    >
                    <small class="text-muted">Separe varios emails por virgula. Se vazio, usa email da empresa e depois usuarios ADMIN/FINANCE.</small>
                    @error('alert_email_recipients')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group" style="max-width: 320px;">
                    <label class="form-label">Dias sem atualizacao da OS</label>
                    <input
                        type="number"
                        min="1"
                        name="alert_work_order_stale_days"
                        class="form-control @error('alert_work_order_stale_days') is-invalid @enderror"
                        value="{{ old('alert_work_order_stale_days', $settings->get('alert_work_order_stale_days')?->value ?? 2) }}"
                        required
                    >
                    @error('alert_work_order_stale_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
