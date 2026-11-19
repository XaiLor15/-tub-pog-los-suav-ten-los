@props(['tone' => 'info', 'icon' => 'info'])
<div {{ $attributes->class(['flex items-start gap-3 rounded-xl border px-4 py-3 text-[0.9375rem]', match ($tone) { 'warning' => 'border-warning/30 bg-warning-bg text-warning', default => 'border-info/25 bg-info-bg text-foreground' }]) }}>
    <span class="{{ $tone === 'warning' ? '' : 'text-info' }}">{{ icon($icon, 'mt-0.5 size-5') }}</span>
    <div>{{ $slot }}</div>
</div>
