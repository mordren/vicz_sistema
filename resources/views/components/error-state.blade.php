@props([
    'title'   => 'Ocorreu um erro',
    'message' => null,
])

<div class="error-state" role="alert">
    <svg class="error-state-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
    </svg>
    <div class="error-state-body">
        <div class="error-state-title">{{ $title }}</div>
        @if ($message)
            <div class="error-state-message">{{ $message }}</div>
        @endif
        @if ($slot->isNotEmpty())
            <div class="error-state-message" style="margin-top:4px;">{{ $slot }}</div>
        @endif
    </div>
</div>
