@extends('layouts.app')

@section('title', 'Detalhe da Conta a Pagar - VICZ Implementos')

@section('content')
    <x-page-header
        title="Conta {{ $payable->id }}"
        subtitle="Visualizacao em construcao"
    >
        <a href="{{ route('payables.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    <x-card title="Resumo">
        <div class="text-muted">Esta tela sera implementada na proxima fase do projeto.</div>
    </x-card>
@endsection
