@props([
    'rows'    => 5,
    'cols'    => 4,
    'message' => 'Carregando...',
])

<div role="status" aria-label="{{ $message }}" aria-live="polite">
    <span class="sr-only">{{ $message }}</span>
    @for ($i = 0; $i < $rows; $i++)
        @php $width = [60, 80, 45, 90, 70][$i % 5]; @endphp
        <div class="skeleton skeleton-row" style="width: {{ $width }}%;"></div>
    @endfor
</div>
