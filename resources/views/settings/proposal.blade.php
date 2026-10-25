@extends('layouts.app')

@section('title', 'Configurações de Propostas')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Configurações de Propostas</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-ghost">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Voltar
        </a>
    </div>

    <div class="card" style="max-width: 920px;">
        <div class="card-body">
        <form method="POST" action="{{ route('settings.proposal.update') }}" novalidate>
            @csrf
            @method('PATCH')

            <div class="grid-2" style="gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Probabilidade Padrão (%)</label>
                    <input type="number" min="0" max="100" name="default_proposal_probability" class="form-control @error('default_proposal_probability') is-invalid @enderror" value="{{ old('default_proposal_probability', $settings->get('default_proposal_probability')?->value ?? 50) }}" required>
                    @error('default_proposal_probability')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Dias para Follow-up em Atraso</label>
                    <input type="number" min="1" name="follow_up_overdue_days" class="form-control @error('follow_up_overdue_days') is-invalid @enderror" value="{{ old('follow_up_overdue_days', $settings->get('follow_up_overdue_days')?->value ?? 3) }}" required>
                    @error('follow_up_overdue_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Fechar Propostas como Perdidas após (dias)</label>
                    <input type="number" min="1" name="auto_close_lost_days" class="form-control @error('auto_close_lost_days') is-invalid @enderror" value="{{ old('auto_close_lost_days', $settings->get('auto_close_lost_days')?->value) }}">
                    @error('auto_close_lost_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
