@extends('layouts.app')

@section('title', 'Editar Proposta - VICZ Implementos')

@section('content')
    <x-page-header
        title="Editar {{ $proposal->number }}"
        subtitle="Ajuste comercial e probabilidade"
    >
        <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-ghost">Ver detalhe</a>
    </x-page-header>

    <x-card title="Atualizar Proposta">
        <form method="POST" action="{{ route('proposals.update', $proposal) }}">
            @csrf
            @method('PUT')

            @if($errors->any())
                <x-error-state
                    title="Não foi possível atualizar"
                    message="Revise os campos obrigatórios antes de salvar as alterações."
                />
            @endif

            @include('proposals.partials.form', ['proposal' => $proposal])

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                <a href="{{ route('proposals.show', $proposal) }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar alterações</button>
            </div>
        </form>
    </x-card>
@endsection
