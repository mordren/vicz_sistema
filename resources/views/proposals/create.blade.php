@extends('layouts.app')

@section('title', 'Nova Proposta - VICZ Implementos')

@section('content')
    <x-page-header
        title="Nova Proposta"
        subtitle="Pipeline comercial com previsão de fechamento"
    >
        <a href="{{ route('proposals.index') }}" class="btn btn-ghost">Voltar</a>
    </x-page-header>

    <x-card title="Dados da Proposta">
        <form method="POST" action="{{ route('proposals.store') }}">
            @csrf

            @if($errors->any())
                <x-error-state
                    title="Não foi possível salvar"
                    message="Revise os campos obrigatórios para concluir o cadastro da proposta."
                />
            @endif

            @include('proposals.partials.form')

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                <a href="{{ route('proposals.index') }}" class="btn btn-ghost">Cancelar</a>
                <button type="submit" class="btn btn-primary">Criar proposta</button>
            </div>
        </form>
    </x-card>
@endsection
