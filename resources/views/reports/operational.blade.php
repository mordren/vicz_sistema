@extends('layouts.app')

@section('title', 'Relatorio Operacional - VICZ Implementos')

@section('content')
    <x-page-header title="Relatorio Operacional" subtitle="Em construcao" />

    <x-card title="Resumo">
        <x-empty-state icon="report" title="Sem dados processados" message="Este relatorio sera implementado na proxima fase." />
    </x-card>
@endsection
