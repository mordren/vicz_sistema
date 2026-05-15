@extends('layouts.app')

@section('title', 'Editar Conta a Receber - VICZ Implementos')

@section('content')
    <x-page-header
        title="Editar Recebível #{{ $receivable->id }}"
        subtitle="Atualizar dados previstos de recebimento"
    >
        <a href="{{ route('receivables.show', $receivable) }}" class="btn btn-ghost">Ver detalhe</a>
    </x-page-header>

    @if($errors->any())
        <x-error-state title="Existem campos inválidos" message="Revise os dados do formulário e tente novamente." />
    @endif

    <x-card title="Edição de Recebível">
        <form method="POST" action="{{ route('receivables.update', $receivable) }}">
            @csrf
            @method('PUT')
            @include('receivables._form', ['submitLabel' => 'Salvar alterações'])
        </form>
    </x-card>
@endsection
