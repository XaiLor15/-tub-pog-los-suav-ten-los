{{-- ProgressBar: value null = no expected amount. Grows from 0 on load. --}}
@props(['value' => null, 'tone' => null, 'label' => null])
@php
    $pct = $value === null ? 0 : max(0, min(100, (float) $value));
    $bar = $tone ?? ($value !== null && $value >= 100 ? 'bg-success' : 'bg-primary');
@endphp
<div {{ $attributes->class(['h-2 w-full overflow-hidden rounded-full bg-surface-2']) }} role="progressbar"
     aria-valuemin="0" aria-valuemax="100" @if ($value !== null) aria-valuenow="{{ round($pct) }}" @endif aria-label="{{ $label ?? 'ຄວາມຄືບໜ້າ' }}">
    <div class="bar-grow h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
</div>
