@props([
    'title'   => 'Nenhum registro encontrado',
    'message' => 'Ainda não há dados para exibir.',
    'icon'    => 'default',  // default | payable | receivable | workorder | proposal | alert | report
])

@php
$icons = [
    'default'     => '<path d="M20 6h-2.18c.07-.44.18-.88.18-1.36C18 2.06 15.96 0 13.36 0c-1.3 0-2.48.54-3.36 1.4C9.12.54 7.94 0 6.64 0 4.04 0 2 2.06 2 4.64c0 .48.11.92.18 1.36H0v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-9 13H4v-2h7v2zm0-4H4v-2h7v2zm9 4h-7v-2h7v2zm0-4h-7v-2h7v2z"/>',
    'payable'     => '<path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>',
    'workorder'   => '<path d="M22.7 19l-9.1-9.1a7.006 7.006 0 0 0-8.6-8.6L8.5 4.8 4.8 8.5 1.3 5A7 7 0 0 0 9.9 13.6L19 22.7c.39.39 1.02.39 1.41 0l2.3-2.3c.38-.38.38-1.02-.01-1.4z"/>',
    'proposal'    => '<path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>',
    'alert'       => '<path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>',
    'report'      => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>',
];

$svgPath = $icons[$icon] ?? $icons['default'];
@endphp

<div class="empty-state" role="status" style="width:100%;max-width:100%;box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:24px 16px;">
    <div class="empty-state-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">{!! $svgPath !!}</svg>
    </div>
    <p class="empty-state-title">{{ $title }}</p>
    <p class="empty-state-message">{{ $message }}</p>
    @if ($slot->isNotEmpty())
        <div style="margin-top: 8px;">
            {{ $slot }}
        </div>
    @endif
</div>
