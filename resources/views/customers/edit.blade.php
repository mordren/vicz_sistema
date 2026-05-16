@extends('layouts.app')

@section('title', 'Editar Cliente - VICZ Implementos')

@section('content')
    <x-page-header title="Editar cliente" subtitle="Atualize dados cadastrais e status do cliente">
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost">Ver detalhe</a>
    </x-page-header>

    @if($errors->any())
        <x-error-state title="Existem campos invalidos" message="Revise os dados e tente novamente." />
    @endif

    <x-card title="Dados do cliente">
        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')
            @include('customers._form', ['submitLabel' => 'Salvar alteracoes'])
        </form>
    </x-card>
@endsection
