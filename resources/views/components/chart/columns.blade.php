{{--
    Single-series column chart (no legend: the panel title names the series). Columns can link somewhere.
    <x-chart.columns caption="…" unit="ຄົນ" :items="[['label' => 'Phaj 1', 'value' => 12, 'href' => '…'], …]" />
    Values are written on the caps when there are few columns; otherwise only the largest is labelled.
--}}
@props(['items', 'caption', 'unit' => '', 'height' => 170, 'color' => 'var(--chart-1)'])
@php
    $count = max(1, count($items));
    $max = max(1, collect($items)->max('value') ?? 0);
    $labelAll = $count <= 10;
@endphp
<figure {{ $attributes->class(['chart']) }} data-bars>
    <figcaption class="sr-only">{{ $caption }}</figcaption>
    <div class="relative border-b border-input" style="height: {{ $height }}px">
        <ul class="absolute inset-0 grid gap-1.5 sm:gap-2.5" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))">
            @foreach ($items as $item)
                @php
                    $pct = $item['value'] / $max * 100;
                    $isMax = $item['value'] === $max;
                    $tag = isset($item['href']) ? 'a' : 'div';
                @endphp
                <li class="h-full">
                    <{{ $tag }} @if ($tag === 'a') href="{{ $item['href'] }}" @else tabindex="0" @endif class="chart-col group flex-col !items-center !justify-end"
                       aria-label="{{ $item['label'] }}: {{ \App\Support\Format::number($item['value']) }} {{ $unit }}">
                        @if ($labelAll || $isMax)
                            <span class="num relative mb-1 text-[0.75rem] font-semibold {{ $isMax ? 'text-foreground' : 'text-muted' }}" aria-hidden="true">{{ \App\Support\Format::number($item['value']) }}</span>
                        @endif
                        <span class="chart-bar bar-rise !w-[min(2.25rem,70%)] transition-[filter] group-hover:brightness-110" style="height: {{ $item['value'] ? max(1.5, $pct * 0.86) : 0 }}%; background: {{ $color }}"></span>
                    </{{ $tag }}>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="mt-2 grid gap-1.5 text-center text-caption text-muted sm:gap-2.5" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))" aria-hidden="true">
        @foreach ($items as $item)<span class="truncate">{{ $item['short'] ?? $item['label'] }}</span>@endforeach
    </div>
</figure>
