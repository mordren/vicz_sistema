@props([
    'variant' => 'neutral',  // success | warning | danger | info | neutral | primary
    'label',
    'dot' => true,
])

{{--
  Variants:
    success → green  (paid, received, delivered, active)
    warning → amber  (open, pending, negotiating)
    danger  → red    (overdue, critical, cancelled/lost when needed)
    info    → blue   (info alerts, sent proposals)
    neutral → grey   (cancelled, resolved, general)
    primary → navy   (approved, waiting)
--}}

<span {{ $attributes->class(['badge', 'badge-' . $variant]) }}>
    {{ $label }}
</span>
