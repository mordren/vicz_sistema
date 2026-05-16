@extends('layouts.app')

@section('title', 'Editar Conta a Pagar - VICZ Implementos')

@section('content')
    <x-page-header
        title="Editar Conta {{ $payable->id }}"
        subtitle="Atualize dados da parcela {{ $payable->installment_number }}/{{ $payable->total_installments }}"
    >
        <a href="{{ route('payables.show', $payable) }}" class="btn btn-ghost">Ver detalhe</a>
    </x-page-header>

    @if($errors->any())
        <x-error-state title="Existem campos invalidos" message="Revise os dados do formulario e tente novamente." />
    @endif

    <x-card title="Edicao da Conta a Pagar">
        <form method="POST" action="{{ route('payables.update', $payable) }}">
            @csrf
            @method('PUT')
            @include('payables._form', ['submitLabel' => 'Salvar alteracoes'])
        </form>
    </x-card>
@endsection
