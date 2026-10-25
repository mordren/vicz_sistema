@props(['column', 'label', 'sorting' => ['sort' => null, 'direction' => null]])

@php
    $active = ($sorting['sort'] ?? null) === $column;
    $direction = $active ? $sorting['direction'] : null;
    $next = $active && $direction === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next, 'page' => null]);
@endphp

<th {{ $attributes }} @if($active) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ $url }}" class="sort-link {{ $active ? 'is-active' : '' }}" title="Ordenar por {{ mb_strtolower($label) }} ({{ $next === 'asc' ? 'crescente' : 'decrescente' }})">
        {{ $label }}
        <span class="sort-indicator" aria-hidden="true">{{ $active ? ($direction === 'asc' ? '▲' : '▼') : '⇅' }}</span>
    </a>
</th>
