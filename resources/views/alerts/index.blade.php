@extends('layouts.app')

@section('title', 'Alertas - VICZ Implementos')

@section('content')
    <x-page-header
        title="Alertas"
        subtitle="Monitoramento de eventos criticos"
    />

    <x-card title="Lista de Alertas">
        <x-empty-state
            icon="alert"
            title="Sem alertas para listar"
            message="Os alertas do sistema serao exibidos aqui."
        />
    </x-card>
@endsection
