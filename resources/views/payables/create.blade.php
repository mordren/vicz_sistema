@extends('layouts.app')

@section('title', 'Nova Conta a Pagar - VICZ Implementos')

@section('content')
    <x-page-header
        title="Nova Conta a Pagar"
        subtitle="Cadastro em construcao"
    />

    <x-card title="Formulario">
        <x-error-state
            title="Funcionalidade indisponivel"
            message="A criacao de contas a pagar sera implementada na proxima fase."
        >
            <a href="{{ route('payables.index') }}" class="btn btn-secondary">Voltar para lista</a>
        </x-error-state>
    </x-card>
@endsection
