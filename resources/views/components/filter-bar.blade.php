@props([
    'label' => 'Filtros',
])

<div class="filter-bar" role="search">
    @if ($label)
        <span class="filter-bar-label">{{ $label }}</span>
    @endif
    {{ $slot }}
</div>
