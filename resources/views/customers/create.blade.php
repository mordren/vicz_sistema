@extends('layouts.app')

@section('title', 'Novo Cliente - VICZ Implementos')

@section('content')
    <x-page-header title="Novo cliente" subtitle="Cadastro de cliente para propostas, OS e recebiveis">
        <a href="{{ route('customers.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    @if($errors->any())
        <x-error-state title="Existem campos invalidos" message="Revise os dados e tente novamente." />
    @endif

    <x-card title="Dados do cliente">
        <form method="POST" action="{{ route('customers.store') }}" data-customer-form>
            @csrf
            @include('customers._form', ['submitLabel' => 'Criar cliente'])
        </form>
    </x-card>
@endsection
