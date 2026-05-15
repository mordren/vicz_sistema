@extends('layouts.app')

@section('title', 'Auditoria - VICZ Implementos')

@section('content')
    <x-page-header
        title="Auditoria"
        subtitle="Rastreabilidade de alteracoes"
    />

    <x-card title="Registros de Auditoria">
        <x-empty-state
            icon="report"
            title="Sem registros para exibir"
            message="Os eventos de auditoria serao exibidos aqui."
        />
    </x-card>
@endsection
