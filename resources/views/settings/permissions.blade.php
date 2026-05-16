@extends('layouts.app')

@section('title', 'Permissões por Role')

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Permissões por Role</h1>
        <p class="page-subtitle">Visualize as permissões de cada role no sistema</p>
    </div>

    @foreach($rolePermissions as $role => $categories)
        <div class="card" style="margin-bottom: 32px;">
            <h3 style="margin-top: 0; font-size: 18px; font-weight: 600; color: #111827; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px;">{{ $role }}</h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-top: 24px;">
                @foreach($categories as $category => $permissions)
                    <div>
                        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #374151;">{{ $category }}</h4>
                        <ul style="margin: 0; padding: 0; list-style: none;">
                            @foreach($permissions as $permission)
                                <li style="padding: 8px 0; color: #6b7280; font-size: 14px; display: flex; align-items: center;">
                                    <svg style="width: 16px; height: 16px; margin-right: 8px; color: #10b981;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
    @endforeach

</div>
@endsection
