{{-- StatCard: label, value, hint, icon?, tone? (value colour) --}}
@props(['label', 'value', 'hint' => null, 'icon' => null, 'tone' => null, 'hintTone' => null])
@php
    $valueColor = match ($tone) { 'success' => 'text-success', 'destructive' => 'text-destructive', 'primary' => 'text-primary', default => 'text-foreground' };
    $hintColor = match ($hintTone) { 'success' => 'text-success font-medium', 'warning' => 'text-warning font-medium', default => 'text-muted' };
@endphp
<div {{ $attributes->class(['card card-pad']) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="text-sm font-medium text-muted">{{ $label }}</p>
        @if ($icon)
            <span class="inline-flex size-9 items-center justify-center rounded-lg bg-primary-soft text-primary">{{ icon($icon, 'size-[1.125rem]') }}</span>
        @endif
    </div>
    <p class="num mt-1 text-[1.75rem] leading-tight font-bold {{ $valueColor }}">{{ $value }}</p>
    @if ($hint || isset($extra))
        <div class="mt-1 text-[0.8125rem] {{ $hintColor }}">{{ $hint }}{{ $extra ?? '' }}</div>
    @endif
</div>
