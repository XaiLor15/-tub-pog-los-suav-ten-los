{{--
    StatCard: label · value · hint, with an optional icon, value tone, hint tone, trend arrow and link.
    Numeric values ("10.450.000 ₭", "124 ຄົນ", "22") count up when they scroll into view (motion.js).
    Only cards with an href lift on hover — static figures do not pretend to be clickable.
--}}
@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => null, 'hintTone' => null, 'trend' => null, 'href' => null])
@php
    $valueColor = match ($tone) { 'success' => 'text-success', 'destructive' => 'text-destructive', 'primary' => 'text-primary', default => 'text-foreground' };
    $hintColor = match ($hintTone) { 'success' => 'text-success font-medium', 'warning' => 'text-warning font-medium', 'destructive' => 'text-destructive font-medium', default => 'text-muted' };
    $count = preg_match('/^(\d{1,3}(?:\.\d{3})*|\d+)(\s.*)?$/u', (string) $value, $m) ? ['to' => str_replace('.', '', $m[1]), 'suffix' => $m[2] ?? ''] : null;
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['card group relative block overflow-hidden p-5', 'card-hover focus-visible:shadow-[0_0_0_3px_var(--ring)] focus-visible:outline-none' => $href]) }}>
    <span class="pointer-events-none absolute -top-12 -right-12 size-32 rounded-full bg-primary-soft opacity-60 blur-2xl transition-opacity duration-300 group-hover:opacity-100" aria-hidden="true"></span>
    <div class="relative flex items-start justify-between gap-2">
        @if ($icon)
            <span class="stat-icon size-10 rounded-xl transition-transform duration-300 group-hover:scale-105 group-hover:-rotate-3">{{ icon($icon, 'size-5') }}</span>
        @endif
        @if ($href)
            <span class="text-muted opacity-0 transition-all duration-200 group-hover:translate-x-0.5 group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">{{ icon('arrow-up-right', 'size-4') }}</span>
        @endif
    </div>
    <p class="relative mt-3 text-sm font-medium text-muted">{{ $label }}</p>
    <p class="num relative mt-0.5 text-kpi font-bold tracking-tight {{ $valueColor }}" @if ($count) data-countup="{{ $count['to'] }}" data-suffix="{{ $count['suffix'] }}" @endif>{{ $value }}</p>
    @if ($hint || isset($extra))
        <div class="relative mt-1.5 flex items-center gap-1 text-caption {{ $hintColor }}">
            @if ($trend){{ icon($trend === 'down' ? 'trending-down' : 'trending-up', 'size-3.5 shrink-0') }}@endif
            <span class="min-w-0">{{ $hint }}{{ $extra ?? '' }}</span>
        </div>
    @endif
</{{ $tag }}>
