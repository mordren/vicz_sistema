@props([
    'title',
    'subtitle' => null,
    'breadcrumb' => null,  // e.g. [['label'=>'Home','route'=>'dashboard'],['label'=>'Payables']]
])

<div class="page-header">
    <div class="page-header-title-group">
        @if ($breadcrumb)
            <nav class="breadcrumb" aria-label="Breadcrumb">
                @foreach ($breadcrumb as $crumb)
                    @if (!$loop->last)
                        <a href="{{ isset($crumb['route']) ? route($crumb['route']) : ($crumb['url'] ?? '#') }}">
                            {{ $crumb['label'] }}
                        </a>
                        <span class="breadcrumb-sep" aria-hidden="true">&#8250;</span>
                    @else
                        <span class="breadcrumb-current" aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif

        <h1 class="page-title">{{ $title }}</h1>

        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="page-header-actions">
            {{ $slot }}
        </div>
    @endif
</div>
