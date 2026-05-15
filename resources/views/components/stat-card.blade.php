@props([
    'label',
    'value',
    'delta'   => null,  // e.g. '+12%'
    'trend'   => null,  // positive | negative | neutral
    'icon'    => null,  // SVG path string
    'iconVariant' => 'navy',  // navy | green | red | amber
])

<div class="stat-card">
    @if ($icon)
        <div class="stat-card-icon stat-card-icon-{{ $iconVariant }}">
            <svg viewBox="0 0 24 24" aria-hidden="true">{!! $icon !!}</svg>
        </div>
    @endif

    <div class="stat-card-label">{{ $label }}</div>
    <div class="stat-card-value">{{ $value }}</div>

    @if ($delta)
        @php $trendClass = $trend ?? 'neutral'; @endphp
        <div class="stat-card-delta {{ $trendClass }}">
            @if ($trend === 'positive')
                <svg viewBox="0 0 24 24"><path d="M7 14l5-5 5 5z"/></svg>
            @elseif ($trend === 'negative')
                <svg viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>
            @endif
            {{ $delta }}
        </div>
    @endif
</div>
