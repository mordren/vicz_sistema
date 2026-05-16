@extends('layouts.app')

@section('title', 'Detalhe do Alerta - VICZ Implementos')

@section('content')
    @php
        use App\Enums\AlertStatus;
        use App\Support\BadgeHelper;
        $related = $alert->relatedRoute();
    @endphp

    <x-page-header
        title="Alerta {{ $alert->id }}"
        subtitle="Detalhamento e tratamento do alerta"
    >
        <a href="{{ route('alerts.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    <x-card title="Resumo do alerta">
        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
            <div>
                <div class="form-label">Titulo</div>
                <div style="font-weight:600;">{{ $alert->title }}</div>
            </div>

            <div>
                <div class="form-label">Tipo</div>
                <div>{{ $alert->type }}</div>
            </div>

            <div>
                <div class="form-label">Severidade</div>
                <x-status-badge :variant="BadgeHelper::variant($alert->severity)" :label="BadgeHelper::label($alert->severity)" />
            </div>

            <div>
                <div class="form-label">Status</div>
                <x-status-badge :variant="BadgeHelper::variant($alert->status)" :label="BadgeHelper::label($alert->status)" />
            </div>

            <div>
                <div class="form-label">Criado em</div>
                <div>{{ $alert->created_at?->format('d/m/Y H:i') }}</div>
            </div>

            <div>
                <div class="form-label">Resolvido em</div>
                <div>{{ $alert->resolved_at?->format('d/m/Y H:i') ?? '-' }}</div>
            </div>
        </div>

        <div class="mt-16">
            <div class="form-label">Mensagem</div>
            <p style="margin:0;">{{ $alert->message }}</p>
        </div>

        <div class="mt-16" style="display:flex;gap:8px;flex-wrap:wrap;">
            @if($related)
                <a href="{{ route($related['name'], $related['params']) }}" class="btn btn-secondary">Abrir registro relacionado</a>
            @endif

            @if($alert->status === AlertStatus::OPEN)
                <form method="POST" action="{{ route('alerts.read', $alert) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-ghost">Marcar como lido</button>
                </form>
            @endif

            @if($alert->status !== AlertStatus::RESOLVED)
                <form method="POST" action="{{ route('alerts.resolve', $alert) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary">Marcar como resolvido</button>
                </form>
            @endif
        </div>
    </x-card>
@endsection
