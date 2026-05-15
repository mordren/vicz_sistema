@extends('layouts.app')

@section('title', 'Editar Conta a Pagar - VICZ Implementos')

@section('content')
    <x-page-header
        title="Editar Conta {{ $payable->id }}"
        subtitle="Edicao em construcao"
    >
        <a href="{{ route('payables.show', $payable) }}" class="btn btn-ghost">Ver detalhe</a>
    </x-page-header>

    <x-card title="Edicao">
        <x-error-state
            title="Edicao ainda nao disponivel"
            message="A atualizacao de contas a pagar sera implementada na proxima fase."
        />
    </x-card>
@endsection
