@extends('layouts.app')

@section('title', 'Painel de Gestão — VICZ Implementos')

@section('content')
@php
    use App\Enums\AlertSeverity;
    use App\Support\BadgeHelper;
@endphp

@include('dashboard._header')

@include('dashboard._summary-cards')

@include('dashboard._attention')

<div class="db-main-grid">
    @if($canSeeFinance)
        @include('dashboard._finance')
    @endif

    @include('dashboard._operations')

    @include('dashboard._sales')

    @include('dashboard._customers')
</div>

@endsection

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════
   Dashboard — VICZ Implementos
   All colors use tokens from designer/SKILL.md only.
   No third-party palette values (no Tailwind swatches).
   ═══════════════════════════════════════════════════════════ */

/* ── Derived local constants (still on-brand) ── */
/* ok    → --secondary #006d41 + --secondary-container #90f4b7  */
/* warn  → lighter than error-container; uses same red family   */
/* info  → --status-info #0D6EFD at 8% opacity on white         */
/* critical → --error-container #ffdad6 + --on-error-container  */

/* ── Page header ──────────────────────────────────────────── */
.db-page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}

.db-page-header__body {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.db-page-header__title {
    font-family: var(--font-brand);
    font-size: 28px;
    font-weight: 700;
    color: var(--primary);
    line-height: 1.15;
    margin: 0;
}

.db-page-header__subtitle {
    font-size: 14px;
    color: var(--on-surface-variant);
    margin: 2px 0 0;
}

.db-page-header__date {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: var(--on-surface-variant);
    margin-top: 6px;
}

.db-page-header__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

/* ── KPI cards ───────────────────────────────────────────── */
.db-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}

.db-kpi-card {
    background: var(--surface);
    border: 1px solid var(--outline-variant);
    border-left-width: 4px;
    border-radius: var(--radius-md);
    padding: 16px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    min-width: 0;
    transition: box-shadow .15s;
}

.db-kpi-card:hover { box-shadow: var(--shadow); }

/* All positive/ok states → secondary green */
.db-kpi-card--ok       { border-left-color: var(--secondary); }
.db-kpi-card--positive { border-left-color: var(--secondary); }
.db-kpi-card--alert    { border-left-color: var(--secondary); }
/* Warning/danger → error red */
.db-kpi-card--warning  { border-left-color: var(--error); }
.db-kpi-card--danger   { border-left-color: var(--error); }
/* Informational → status-info blue */
.db-kpi-card--info     { border-left-color: var(--status-info); }

.db-kpi-card__icon {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    border-radius: var(--radius-sm);
    background: var(--surface-container);
    display: flex;
    align-items: center;
    justify-content: center;
}

.db-kpi-card__icon svg { width: 18px; height: 18px; fill: var(--on-surface-variant); }

.db-kpi-card__body { min-width: 0; flex: 1; }

.db-kpi-card__label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--on-surface-variant);
    margin-bottom: 4px;
}

.db-kpi-card__value {
    display: block;
    font-family: var(--font-brand);
    font-size: 22px;
    font-weight: 700;
    color: var(--deep-navy);
    line-height: 1.15;
    font-feature-settings: "tnum";
    overflow: hidden;
    text-overflow: ellipsis;
}

.db-kpi-card__value--danger { color: var(--error); }

.db-kpi-card__sub {
    display: block;
    font-size: 11px;
    color: var(--on-surface-variant);
    margin-top: 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.db-kpi-card__sub--danger {
    color: var(--on-error-container);
    font-weight: 600;
}

/* ── Attention cards ─────────────────────────────────────── */
.db-attention-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
    margin-bottom: 8px;
}

.db-attention-card {
    display: grid;
    grid-template-columns: 28px minmax(0, 1fr);
    align-items: start;
    gap: 10px;
    padding: 12px 14px;
    border-radius: var(--radius-md);
    border: 1px solid transparent;
    width: 100%;
}

/* Critical → full error-container (#ffdad6) */
.db-attention-card--critical {
    background: var(--error-container);
    border-color: #ffb4ab;
}
/* Warning → lighter red tint (same family, less intense) */
.db-attention-card--warning {
    background: #fff0ec;
    border-color: #ffd0c8;
}

.db-attention-card__icon {
    flex-shrink: 0;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.db-attention-card__icon svg { width: 20px; height: 20px; }
.db-attention-card--critical .db-attention-card__icon svg { fill: var(--on-error-container); }
.db-attention-card--warning  .db-attention-card__icon svg { fill: var(--error); }

.db-attention-card__body { flex: 1; min-width: 0; }

.db-attention-card__title {
    font-weight: 700;
    font-size: 13px;
    color: var(--on-surface);
    margin: 0 0 3px;
}

.db-attention-card--critical .db-attention-card__title { color: var(--on-error-container); }
.db-attention-card--warning  .db-attention-card__title { color: var(--error); }

.db-attention-card__desc {
    font-size: 12px;
    color: var(--on-surface-variant);
    margin: 0;
    line-height: 1.4;
}

.db-attention-card__amount {
    font-size: 13px;
    font-weight: 700;
    margin: 4px 0 0;
    color: var(--on-error-container);
    font-feature-settings: "tnum";
}

.db-attention-card__action {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    margin-top: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    text-decoration: none;
    white-space: nowrap;
    grid-column: 2;
    justify-self: start;
}

.db-attention-card--critical .db-attention-card__action { color: var(--on-error-container); }
.db-attention-card--warning  .db-attention-card__action { color: var(--error); }

/* ── Main 2-column grid ──────────────────────────────────── */
.db-main-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    margin-top: 20px;
}

/* ── Section helpers ─────────────────────────────────────── */
.db-section-header { margin-bottom: 10px; }

.db-section-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--on-surface-variant);
}

.db-section-title {
    font-family: var(--font-brand);
    font-size: 15px;
    font-weight: 600;
    color: var(--deep-navy);
    margin: 0;
}

.db-section-divider {
    height: 1px;
    background: var(--outline-variant);
    margin: 16px 0;
}

/* ── Finance mini-metrics ────────────────────────────────── */
.db-finance-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.db-finance-metric {
    text-align: center;
    padding: 10px 8px;
    border: 1px solid var(--outline-variant);
    border-radius: var(--radius-sm);
    background: var(--surface-muted);
}

.db-finance-metric__label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--on-surface-variant);
    margin-bottom: 4px;
}

.db-finance-metric__value {
    display: block;
    font-family: var(--font-brand);
    font-size: 16px;
    font-weight: 700;
    color: var(--deep-navy);
    font-feature-settings: "tnum";
}

.db-finance-metric__value--green { color: var(--status-success); }
.db-finance-metric__value--red   { color: var(--error); }
/* warn uses error color — no amber token in design system */
.db-finance-metric__value--warn  { color: var(--error); }

/* ── Bar chart ───────────────────────────────────────────── */
.db-chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.db-chart-title {
    font-family: var(--font-brand);
    font-size: 14px;
    font-weight: 600;
    color: var(--deep-navy);
    margin: 0;
}

.db-chart-legend {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: var(--on-surface-variant);
    align-items: center;
}

.db-legend-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 4px;
    vertical-align: middle;
}

.db-legend-dot--green { background: var(--status-success); }
.db-legend-dot--red   { background: var(--error); }

.db-bar-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 4px;
    height: 136px;
    overflow: hidden;
}

.db-bar-col {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.db-bar-wrap {
    width: 100%;
    height: 96px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: 2px;
    min-width: 0;
}

.db-bar {
    width: 100%;
    border-radius: 2px 2px 0 0;
    transition: height .3s ease;
}

/* No opacity reduction — use tokens at full strength */
.db-bar--green { background: var(--status-success); }
.db-bar--red   { background: var(--error); }

.db-bar-label {
    font-size: 10px;
    color: var(--on-surface-variant);
    font-weight: 600;
}

.db-bar-net {
    font-size: 10px;
    font-weight: 600;
    font-feature-settings: "tnum";
}

.db-bar-net--pos { color: var(--status-success); }
.db-bar-net--neg { color: var(--error); }

/* ── Ops / Sales mini-metrics ────────────────────────────── */
.db-ops-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.db-ops-metric {
    text-align: center;
    padding: 10px 8px;
    border: 1px solid var(--outline-variant);
    border-radius: var(--radius-sm);
    background: var(--surface-muted);
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}

.db-ops-metric strong {
    display: block;
    font-family: var(--font-brand);
    font-size: 22px;
    font-weight: 700;
    line-height: 1.1;
    color: var(--deep-navy);
}

.db-ops-metric span {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--on-surface-variant);
    line-height: 1.3;
}

/* ok → secondary green family */
.db-ops-metric--ok {
    border-color: var(--secondary-container);
    background: #eef8f3;
}
.db-ops-metric--ok strong { color: var(--secondary); }

/* danger → full error system */
.db-ops-metric--danger {
    border-color: #ffb4ab;
    background: var(--error-container);
}
.db-ops-metric--danger strong { color: var(--on-error-container); }

/* warn → lighter red tint (same red family, less severe) */
.db-ops-metric--warn {
    border-color: #ffd0c8;
    background: #fff0ec;
}
.db-ops-metric--warn strong { color: var(--error); }

/* info → status-info blue family */
.db-ops-metric--info {
    border-color: #9fc4fd;
    background: #ebf2ff;
}
.db-ops-metric--info strong { color: var(--status-info); }

/* ── Progress bars ───────────────────────────────────────── */
.db-progress-track {
    width: 100%;
    height: 5px;
    border-radius: var(--radius-full);
    background: var(--surface-container-high);
    overflow: hidden;
    margin-bottom: 2px;
}

.db-progress-fill {
    height: 100%;
    border-radius: var(--radius-full);
    background: var(--secondary);
    transition: width .3s;
}

.db-progress-fill--danger { background: var(--error); }

/* ── Status badges (inline pills) ────────────────────────── */
.db-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: var(--radius-full);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    vertical-align: middle;
    margin-left: 4px;
}

.db-badge--danger  { background: var(--error);        color: var(--on-primary); }
.db-badge--warn    { background: #ffd0c8;              color: var(--on-error-container); }
.db-badge--info    { background: var(--status-info);   color: var(--on-primary); }
.db-badge--neutral { background: var(--surface-container-high); color: var(--on-surface-variant); }

/* ── Table row states ────────────────────────────────────── */
.db-row-critical td     { background: var(--error-container) !important; }
.db-row-overdue  td     { background: var(--error-container) !important; }
.db-row-warning  td     { background: #fff0ec !important; }
.dash-alert-critical td { background: var(--error-container) !important; }

/* Inline cell colors */
.td-danger { color: var(--on-error-container); font-weight: 600; }
.td-warn   { color: var(--error);              font-weight: 600; }

/* ── Type badges (Receber / Pagar) ───────────────────────── */
.db-type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: var(--radius-full);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .03em;
}

/* income → secondary-container green */
.db-type-badge--in  {
    background: var(--secondary-container);
    color: var(--on-secondary-container);
}

/* expense → error-container red */
.db-type-badge--out {
    background: var(--error-container);
    color: var(--on-error-container);
}

/* ── Sales funnel ────────────────────────────────────────── */
.db-funnel { display: flex; flex-direction: column; gap: 8px; }

.db-funnel-stage { display: flex; align-items: center; gap: 8px; }

.db-funnel-label {
    width: 90px;
    flex-shrink: 0;
    font-size: 12px;
    font-weight: 600;
    color: var(--on-surface);
    text-align: right;
}

.db-funnel-bar-wrap {
    display: flex;
    align-items: center;
    flex: 1;
    gap: 8px;
    min-width: 0;
    overflow: hidden;
}

.db-funnel-bar {
    height: 20px;
    border-radius: var(--radius-sm);
    transition: width .3s;
    min-width: 4px;
}

.db-funnel-count  { font-size: 12px; font-weight: 700; color: var(--on-surface); white-space: nowrap; }
.db-funnel-amount { font-size: 11px; color: var(--on-surface-variant); font-feature-settings: "tnum"; white-space: nowrap; }

/* ── Probability badge ───────────────────────────────────── */
.db-prob-badge {
    display: inline-block;
    padding: 2px 7px;
    border-radius: var(--radius-full);
    font-size: 11px;
    font-weight: 700;
    background: var(--surface-container);
    color: var(--on-surface);
}

/* ── Customer panels ─────────────────────────────────────── */
.db-customers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-top: 16px;
}

.db-customer-panel {
    border: 1px solid var(--outline-variant);
    border-radius: var(--radius-sm);
    padding: 12px 14px;
}

.db-customer-list {
    margin: 8px 0 0;
    padding: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.db-customer-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding-bottom: 6px;
    border-bottom: 1px dashed var(--outline-variant);
    font-size: 13px;
}

.db-customer-list li:last-child { border-bottom: none; padding-bottom: 0; }

.db-customer-list a {
    color: var(--deep-navy);
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 140px;
}

.db-customer-list a:hover { text-decoration: underline; }

/* ════════════════════════════════════════════════════════════
   Responsive — follows design system breakpoints
   Desktop ≥ 1100px  |  Tablet 768–1099px  |  Mobile < 768px
   ════════════════════════════════════════════════════════════ */

/* ── Tablet (≤ 1100px): collapse 2-col grid to 1-col ─────── */
@media (max-width: 1100px) {
    .db-main-grid            { grid-template-columns: 1fr; }
    .db-kpi-grid             { grid-template-columns: repeat(3, 1fr); }
}

/* ── Small tablet (≤ 768px) ──────────────────────────────── */
@media (max-width: 768px) {
    .db-kpi-grid             { grid-template-columns: repeat(2, 1fr); }
    .db-finance-metrics      { grid-template-columns: repeat(2, 1fr); }
    .db-ops-metrics          { grid-template-columns: repeat(2, 1fr); }
    .db-attention-grid       { grid-template-columns: 1fr; }
    .db-customers-grid       { grid-template-columns: repeat(2, 1fr); }
    .db-bar-chart            { height: 116px; }
    .db-bar-wrap             { height: 76px; }
    .db-funnel-label         { width: 72px; font-size: 11px; }
    .db-funnel-amount        { display: none; }
}

/* ── Mobile (≤ 480px) ────────────────────────────────────── */
@media (max-width: 480px) {
    .db-page-header          { flex-direction: column; }
    .db-page-header__actions { width: 100%; }
    .db-page-header__title   { font-size: 22px; }

    .db-kpi-grid             { grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .db-kpi-card             { padding: 12px; }
    .db-kpi-card__value      { font-size: 18px; }

    .db-finance-metrics      { grid-template-columns: repeat(2, 1fr); }
    .db-ops-metrics          { grid-template-columns: repeat(2, 1fr); }
    .db-customers-grid       { grid-template-columns: 1fr; }

    .db-bar-chart            { height: 96px; }
    .db-bar-wrap             { height: 60px; }
    .db-bar-net              { display: none; }

    .db-funnel-label         { display: none; }
}
</style>
@endpush

