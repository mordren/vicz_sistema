@extends('layouts.app')

@section('title', 'Usuários')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Usuários</h1>
        <a href="{{ route('settings.users.create') }}" class="btn btn-primary">+ Novo Usuário</a>
    </div>

    @if($users->count())
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Criado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge">{{ $user->role->label() }}</span></td>
                            <td>
                                @if($user->active)
                                    <span class="badge" style="background-color: #e0f7e0; color: #1b5e20;">Ativo</span>
                                @else
                                    <span class="badge" style="background-color: #ffe0e0; color: #c62828;">Inativo</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td>
                                <a href="{{ route('settings.users.edit', $user) }}" class="btn btn-sm btn-outline">Editar</a>
                                @if(auth()->user()->id !== $user->id)
                                    <form method="POST" action="{{ route('settings.users.toggle-active', $user) }}" style="display:inline;">
                                        @method('PATCH')
                                        @csrf
                                        <button class="btn btn-sm btn-outline" type="submit">
                                            {{ $user->active ? 'Desativar' : 'Ativar' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    @else
        <div class="card" style="text-align: center; padding: 48px 24px;">
            <p style="font-size: 16px; color: #6b7280;">Nenhum usuário encontrado.</p>
            <a href="{{ route('settings.users.create') }}" class="btn btn-primary" style="margin-top: 16px;">Criar Primeiro Usuário</a>
        </div>
    @endif

</div>
@endsection
