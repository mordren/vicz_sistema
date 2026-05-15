@extends('layouts.app')

@section('title', 'Nova Conta a Receber - VICZ Implementos')

@section('content')
    <x-page-header
        title="Nova Conta a Receber"
        subtitle="Registrar receita prevista por cliente, OS ou venda direta"
    >
        <a href="{{ route('receivables.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    @if($errors->any())
        <x-error-state title="Existem campos inválidos" message="Revise os dados do formulário e tente novamente." />
    @endif

    <x-card title="Dados do Recebível">
        <form method="POST" action="{{ route('receivables.store') }}">
            @csrf
            @include('receivables._form', ['submitLabel' => 'Criar recebível'])
        </form>
    </x-card>
@endsection
