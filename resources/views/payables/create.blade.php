@extends('layouts.app')

@section('title', 'Nova Conta a Pagar - VICZ Implementos')

@section('content')
    <x-page-header
        title="Nova Conta a Pagar"
        subtitle="Registrar despesa unica ou parcelada"
    >
        <a href="{{ route('payables.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    @if($errors->any())
        <x-error-state title="Existem campos invalidos" message="Revise os dados do formulario e tente novamente." />
    @endif

    <x-card title="Dados da Conta a Pagar">
        <form method="POST" action="{{ route('payables.store') }}" enctype="multipart/form-data">
            @csrf
            @include('payables._form', ['submitLabel' => 'Criar conta'])
        </form>
    </x-card>
@endsection
