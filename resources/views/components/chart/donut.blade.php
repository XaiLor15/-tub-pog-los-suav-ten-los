{{--
    Donut: parts of one whole with a centre total and a legend carrying value + share.
    <x-chart.donut caption="…" center-label="ທັງໝົດ" :segments="[['label' => 'ຊາຍ', 'value' => 120, 'color' => 'var(--chart-1)'], …]" />
--}}
@props(['segments', 'caption', 'centerLabel' => 'ທັງໝົດ'])
@php
    $total = max(0, collect($segments)->sum('value'));
    $gap = count(array_filter($segments, fn ($s) => $s['value'] > 0)) > 1 ? 0.8 : 0;   // surface gap between segments (pathLength units)
    $offset = 0;
    $arcs = [];
    foreach ($segments as $segment) {
        $share = $total ? $segment['value'] / $total * 100 : 0;
        $arcs[] = $segment + ['share' => $share, 'start' => $offset];
        $offset += $share;
    }
    $summary = $caption.': '.collect($arcs)->map(fn ($a) => $a['label'].' '.\App\Support\Format::number($a['value']).' ('.percent($a['share'], 0).')')->implode(', ');
@endphp
<figure {{ $attributes->class(['flex flex-col items-center gap-5 @[22rem]:flex-row @[22rem]:gap-6']) }}>
    <div class="relative size-40 shrink-0">
        <svg viewBox="0 0 100 100" class="donut size-full -rotate-90" role="img" aria-label="{{ $summary }}">
            <circle cx="50" cy="50" r="40" fill="none" stroke="var(--chart-track)" stroke-width="12" />
            @foreach ($arcs as $arc)
                @if ($arc['share'] > 0)
                    <circle class="donut-seg donut-draw" cx="50" cy="50" r="40" fill="none" pathLength="100" stroke-width="12"
                            stroke="{{ $arc['color'] }}"
                            style="stroke-dasharray: {{ max(0.1, $arc['share'] - $gap) }} {{ 100 - max(0.1, $arc['share'] - $gap) }}; stroke-dashoffset: {{ -$arc['start'] }}; --i: {{ $loop->index }}">
                        <title>{{ $arc['label'] }}: {{ \App\Support\Format::number($arc['value']) }} ({{ percent($arc['share'], 0) }})</title>
                    </circle>
                @endif
            @endforeach
        </svg>
        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center" aria-hidden="true">
            <span class="num text-[1.625rem] leading-none font-bold" data-countup="{{ $total }}">{{ \App\Support\Format::number($total) }}</span>
            <span class="mt-1 text-caption text-muted">{{ $centerLabel }}</span>
        </div>
    </div>
    <figcaption class="sr-only">{{ $caption }}</figcaption>
    <ul class="w-full min-w-0 flex-1 space-y-2.5" aria-hidden="true">
        @foreach ($arcs as $arc)
            <li class="flex items-center gap-3">
                <span class="size-3 shrink-0 rounded-[3px]" style="background: {{ $arc['color'] }}"></span>
                <span class="min-w-0 flex-1 truncate text-sm text-muted">{{ $arc['label'] }}</span>
                <span class="num text-[0.9375rem] font-semibold">{{ \App\Support\Format::number($arc['value']) }}</span>
                <span class="num w-11 text-right text-caption text-muted">{{ percent($arc['share'], 0) }}</span>
            </li>
        @endforeach
    </ul>
</figure>
