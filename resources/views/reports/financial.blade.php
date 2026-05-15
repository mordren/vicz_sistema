@extends('layouts.app')

@section('title', 'Relatorio Financeiro - VICZ Implementos')

@section('content')
    <x-page-header title="Relatorio Financeiro" subtitle="Em construcao" />

    <x-card title="Resumo">
        <x-empty-state icon="report" title="Sem dados processados" message="Este relatorio sera implementado na proxima fase." />
    </x-card>
@endsection
