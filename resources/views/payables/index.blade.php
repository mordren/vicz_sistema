@extends('layouts.app')

@section('title', 'Contas a Pagar - VICZ Implementos')

@section('content')
    <x-page-header
        title="Contas a Pagar"
        subtitle="Gestao de obrigacoes e vencimentos"
    >
        <a href="{{ route('payables.create') }}" class="btn btn-primary">Nova conta</a>
    </x-page-header>

    <x-card title="Lista de Contas a Pagar">
        <x-empty-state
            icon="payable"
            title="Nenhuma conta cadastrada"
            message="Os lancamentos serao exibidos aqui assim que forem criados."
        />
    </x-card>
@endsection
