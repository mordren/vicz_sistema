@extends('layouts.app')

@section('title', 'Usuários')

@section('content')
<div class="page-container">
    <x-page-header title="Usuários" subtitle="Contas de acesso ao sistema">
        <a href="{{ route('settings.users.create') }}" class="btn btn-primary">+ Novo Usuário</a>
    </x-page-header>

    @if($users->count())
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <x-sort-th column="name" label="Nome" :sorting="$sorting" />
                        <x-sort-th column="email" label="E-mail" :sorting="$sorting" />
                        <x-sort-th column="role" label="Perfil" :sorting="$sorting" />
                        <x-sort-th column="active" label="Status" :sorting="$sorting" />
                        <x-sort-th column="created_at" label="Criado em" :sorting="$sorting" />
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge badge-primary">{{ $user->role->label() }}</span></td>
                            <td>
                                @if($user->active)
                                    <span class="badge badge-success">Ativo</span>
                                @else
                                    <span class="badge badge-danger">Inativo</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="td-actions">
                                <a href="{{ route('settings.users.edit', $user) }}" class="btn btn-sm btn-outline">Editar</a>
                                @if(auth()->user()->id !== $user->id)
                                    <form method="POST" action="{{ route('settings.users.toggle-active', $user) }}" style="display:inline;">
                                        @method('PATCH')
                                        @csrf
                                        <button class="btn btn-sm btn-outline" type="submit">
                                            {{ $user->active ? 'Desativar' : 'Ativar' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('settings.users.destroy', $user) }}" style="display:inline;">
                                        @method('DELETE')
                                        @csrf
                                        <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm(@js('Excluir o usuário '.$user->name.' permanentemente?'))">
                                            Excluir
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-16">{{ $users->links() }}</div>
    @else
        <div class="card" style="text-align: center; padding: 48px 24px;">
            <p style="font-size: 16px; color: var(--on-surface-variant);">Nenhum usuário encontrado.</p>
            <a href="{{ route('settings.users.create') }}" class="btn btn-primary" style="margin-top: 16px;">Criar Primeiro Usuário</a>
        </div>
    @endif

</div>
@endsection
