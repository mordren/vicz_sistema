@extends('layouts.app')

@section('title', 'Configurações de Anexos')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Configurações de Anexos</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-ghost">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Voltar
        </a>
    </div>

    <div class="card" style="max-width: 920px;">
        <div class="card-body">
        <form method="POST" action="{{ route('settings.attachment.update') }}" novalidate>
            @csrf
            @method('PATCH')

            <div style="display: grid; gap: 16px; max-width: 420px;">
                <div class="form-group">
                    <label class="form-label">Tamanho máximo por arquivo (MB)</label>
                    <input type="number" min="1" max="100" name="max_attachment_size_mb" class="form-control @error('max_attachment_size_mb') is-invalid @enderror" value="{{ old('max_attachment_size_mb', $settings->get('max_attachment_size_mb')?->value ?? 10) }}" required>
                    @error('max_attachment_size_mb')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Extensões permitidas</label>
                    <input type="text" name="allowed_file_extensions" class="form-control @error('allowed_file_extensions') is-invalid @enderror" value="{{ old('allowed_file_extensions', $settings->get('allowed_file_extensions')?->value ?? 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx') }}" required>
                    <small style="display:block; margin-top: 6px; color: #45464e;">Use vírgula para separar, exemplo: pdf,jpg,png</small>
                    @error('allowed_file_extensions')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
