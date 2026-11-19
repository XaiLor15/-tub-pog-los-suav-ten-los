{{-- Grouped bars: income (chart-1) vs expense (chart-2) per month. One y-scale; legend + tooltips + sr table. --}}
@php
    $max = max(1, collect($monthly)->flatMap(fn ($m) => [$m['income'], $m['expense']])->max());
    $h = 150;
@endphp
<div class="relative" data-bars>
    <div class="grid items-end gap-2 border-b border-border" style="grid-template-columns: repeat({{ count($monthly) }}, minmax(0, 1fr)); height: {{ $h + 4 }}px" aria-hidden="true">
        @foreach ($monthly as $m)
            <div class="group relative flex h-full items-end justify-center gap-[2px]">
                <span class="bar-rise w-[min(1.5rem,32%)] rounded-t bg-chart-1" style="height: {{ max($m['income'] ? 2 : 0, round($m['income'] / $max * $h)) }}px"></span>
                <span class="bar-rise w-[min(1.5rem,32%)] rounded-t bg-chart-2" style="height: {{ max($m['expense'] ? 2 : 0, round($m['expense'] / $max * $h)) }}px"></span>
                @php $edge = $loop->index < 2 ? 'left-0' : ($loop->remaining < 2 ? 'right-0' : 'left-1/2 -translate-x-1/2'); @endphp
                <span class="pointer-events-none absolute bottom-full z-10 mb-2 hidden w-max rounded-lg border border-border bg-card px-3 py-2 text-xs shadow-pop group-hover:block group-hover:animate-[slide-up-sm_180ms_var(--ease-out-soft)_both] {{ $edge }}">
                    <span class="block font-semibold">{{ $m['label'] }} {{ substr($m['month'], 0, 4) }}</span>
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-sm bg-chart-1"></span>ລາຍຮັບ <b class="num">{{ money($m['income']) }}</b></span>
                    <span class="flex items-center gap-1.5"><span class="size-2 rounded-sm bg-chart-2"></span>ລາຍຈ່າຍ <b class="num">{{ money($m['expense']) }}</b></span>
                </span>
            </div>
        @endforeach
    </div>
    <div class="mt-2 grid text-center text-[0.8125rem] text-muted" style="grid-template-columns: repeat({{ count($monthly) }}, minmax(0, 1fr))" aria-hidden="true">
        @foreach ($monthly as $m)<span>{{ $m['label'] }}</span>@endforeach
    </div>
    <table class="sr-only">
        <caption>ລາຍຮັບ ແລະ ລາຍຈ່າຍ 6 ເດືອນ</caption>
        <thead><tr><th>ເດືອນ</th><th>ລາຍຮັບ</th><th>ລາຍຈ່າຍ</th></tr></thead>
        <tbody>@foreach ($monthly as $m)<tr><td>{{ $m['label'] }} {{ substr($m['month'], 0, 4) }}</td><td>{{ money($m['income']) }}</td><td>{{ money($m['expense']) }}</td></tr>@endforeach</tbody>
    </table>
</div>
