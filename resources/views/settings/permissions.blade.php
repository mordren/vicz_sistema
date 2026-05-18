@extends('layouts.app')

@section('title', 'Permissões por Role')

@section('content')
<div class="page-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Permissões por Role</h1>
            <p class="page-subtitle">Visualize as permissões de cada role no sistema</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-ghost">
            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            Voltar
        </a>
    </div>

    @foreach($rolePermissions as $role => $categories)
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <span class="card-title">{{ $role }}</span>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px;">
                    @foreach($categories as $category => $permissions)
                        <div>
                            <p style="margin: 0 0 10px 0; font-size: 12px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--on-surface-variant);">{{ $category }}</p>
                            <ul style="margin: 0; padding: 0; list-style: none;">
                                @foreach($permissions as $permission)
                                    <li style="padding: 5px 0; font-size: 13px; color: var(--on-surface); display: flex; align-items: center; gap: 8px;">
                                        <svg style="width: 14px; height: 14px; flex-shrink: 0; color: var(--secondary);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        {{ $permission }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

</div>
@endsection
