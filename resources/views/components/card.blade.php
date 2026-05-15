@props([
    'title'   => null,
    'compact' => false,
    'noBorder' => false,
])

<div {{ $attributes->class(['card', $noBorder ? 'card-no-border' : '']) }}>

    @if ($title || isset($actions))
        <div class="card-header">
            @if ($title)
                <h2 class="card-title">{{ $title }}</h2>
            @endif
            @if (isset($actions))
                <div class="page-header-actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $compact ? 'card-body-compact' : 'card-body' }}">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="card-footer">
            {{ $footer }}
        </div>
    @endif

</div>
