@extends('layouts.app')

@section('title', 'Configurações de Ordem de Serviço')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Configurações de Ordem de Serviço</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline">Voltar</a>
    </div>

    <div class="card" style="max-width: 920px; border: 1px solid #e9ecef; border-radius: 8px;">
        <form method="POST" action="{{ route('settings.work-order.update') }}" novalidate>
            @csrf
            @method('PATCH')

            <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Dias de Aviso de Entrega</label>
                    <input type="number" min="1" name="default_delivery_warning_days" class="form-control @error('default_delivery_warning_days') is-invalid @enderror" value="{{ old('default_delivery_warning_days', $settings->get('default_delivery_warning_days')?->value ?? 7) }}" required>
                    @error('default_delivery_warning_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group" style="display: flex; align-items: end;">
                    <div>
                        <input type="hidden" name="auto_create_receivables" value="0">
                        <label class="form-checkbox">
                            <input type="checkbox" name="auto_create_receivables" value="1" @checked((string) old('auto_create_receivables', $settings->get('auto_create_receivables')?->value ?? '1') === '1')>
                            Criar contas a receber automaticamente
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <input type="hidden" name="require_down_payment_confirmation" value="0">
                    <label class="form-checkbox">
                        <input type="checkbox" name="require_down_payment_confirmation" value="1" @checked((string) old('require_down_payment_confirmation', $settings->get('require_down_payment_confirmation')?->value ?? '1') === '1')>
                        Exigir confirmação financeira da entrada
                    </label>
                </div>

                <div class="form-group">
                    <input type="hidden" name="allow_overpayment" value="0">
                    <label class="form-checkbox">
                        <input type="checkbox" name="allow_overpayment" value="1" @checked((string) old('allow_overpayment', $settings->get('allow_overpayment')?->value ?? '0') === '1')>
                        Permitir recebimento acima do valor contratado
                    </label>
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
