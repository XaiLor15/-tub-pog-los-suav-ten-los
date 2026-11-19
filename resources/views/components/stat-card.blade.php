{{-- StatCard: label, value, hint, icon?, tone? (value colour). Numeric values count up on load (motion.js). --}}
@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => null, 'hintTone' => null])
@php
    $valueColor = match ($tone) { 'success' => 'text-success', 'destructive' => 'text-destructive', 'primary' => 'text-primary', default => 'text-foreground' };
    $hintColor = match ($hintTone) { 'success' => 'text-success font-medium', 'warning' => 'text-warning font-medium', default => 'text-muted' };
    // "10.450.000 ₭" / "124 ຄົນ" / "22" → count target + suffix
    $count = preg_match('/^(\d{1,3}(?:\.\d{3})*|\d+)(\s.*)?$/u', (string) $value, $m) ? ['to' => str_replace('.', '', $m[1]), 'suffix' => $m[2] ?? ''] : null;
@endphp
<div {{ $attributes->class(['card card-pad card-hover group relative overflow-hidden']) }}>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-primary/0 via-primary/50 to-primary/0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
    <div class="flex items-start justify-between gap-2">
        <p class="text-sm font-medium text-muted">{{ $label }}</p>
        @if ($icon)
            <span class="stat-icon transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-3">{{ icon($icon, 'size-[1.125rem]') }}</span>
        @endif
    </div>
    <p class="num mt-1 text-[1.75rem] leading-tight font-bold tracking-tight {{ $valueColor }}" @if ($count) data-countup="{{ $count['to'] }}" data-suffix="{{ $count['suffix'] }}" @endif>{{ $value }}</p>
    @if ($hint || isset($extra))
        <div class="mt-1 text-[0.8125rem] {{ $hintColor }}">{{ $hint }}{{ $extra ?? '' }}</div>
    @endif
</div>
