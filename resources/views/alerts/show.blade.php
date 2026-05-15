@extends('layouts.app')

@section('title', 'Detalhe do Alerta - VICZ Implementos')

@section('content')
    <x-page-header
        title="Alerta {{ $alert->id }}"
        subtitle="Visualizacao em construcao"
    >
        <a href="{{ route('alerts.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    <x-card title="Resumo">
        <div class="text-muted">Esta tela sera implementada na proxima fase do projeto.</div>
    </x-card>
@endsection
