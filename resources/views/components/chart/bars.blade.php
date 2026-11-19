{{--
    Grouped column chart: one y-scale, clean ticks, hairline grid, legend, hover/focus tooltip and a table view.

    <x-chart.bars caption="…" :categories="[['short' => 'ມ.ກ', 'full' => 'ມັງກອນ 2026'], …]"
                  :series="[['label' => 'ລາຍຮັບ', 'color' => 'var(--chart-1)', 'values' => [ … ]], …]" money />
--}}
@props(['categories', 'series', 'caption', 'money' => false, 'height' => 200])
@php
    $count = count($categories);
    $max = max(4, collect($series)->flatMap(fn ($s) => $s['values'])->max() ?? 0);

    // Axis on a 1 / 2 / 2.5 / 5 × 10ⁿ step so tick labels read as round numbers.
    $raw = $max / 4;
    $magnitude = 10 ** floor(log10($raw));
    $step = collect([1, 2, 2.5, 5, 10])->map(fn ($f) => $f * $magnitude)->first(fn ($s) => $s >= $raw);
    $top = ceil($max / $step) * $step;
    $ticks = range(0, $top, $step);

    $compact = function (float $v): string {
        foreach ([1_000_000_000 => 'B', 1_000_000 => 'M', 1_000 => 'K'] as $unit => $suffix) {
            if ($v >= $unit) {
                return rtrim(rtrim(number_format($v / $unit, 1, ',', ''), '0'), ',').$suffix;
            }
        }

        return (string) (int) $v;
    };
    $format = fn ($v) => $money ? money($v) : \App\Support\Format::number($v);
@endphp
<figure {{ $attributes->class(['chart']) }} data-bars>
    <figcaption class="sr-only">{{ $caption }}</figcaption>

    <ul class="mb-4 flex flex-wrap items-center gap-x-5 gap-y-1 text-caption text-muted" aria-label="ຄຳອະທິບາຍ">
        @foreach ($series as $s)
            <li class="flex items-center gap-2"><span class="size-2.5 rounded-[3px]" style="background: {{ $s['color'] }}"></span>{{ $s['label'] }}</li>
        @endforeach
    </ul>

    <div class="flex gap-3">
        <div class="relative w-11 shrink-0 text-right text-[0.75rem] text-muted num" style="height: {{ $height }}px" aria-hidden="true">
            @foreach ($ticks as $tick)
                <span class="absolute right-0 translate-y-1/2 leading-none" style="bottom: {{ $tick / $top * 100 }}%">{{ $compact($tick) }}</span>
            @endforeach
        </div>

        <div class="relative min-w-0 flex-1" style="height: {{ $height }}px">
            @foreach ($ticks as $tick)
                <span class="chart-grid-line {{ $loop->first ? '!border-input' : '' }}" style="bottom: {{ $tick / $top * 100 }}%" aria-hidden="true"></span>
            @endforeach

            <div class="absolute inset-0 grid gap-1 sm:gap-2" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))" role="list">
                @foreach ($categories as $i => $category)
                    @php
                        $summary = $category['full'].': '.collect($series)->map(fn ($s) => $s['label'].' '.$format($s['values'][$i]))->implode(', ');
                        $edge = $i < 1 ? 'left-0' : ($i >= $count - 1 ? 'right-0' : 'left-1/2 -translate-x-1/2');
                    @endphp
                    <div class="chart-col" tabindex="0" role="listitem" aria-label="{{ $summary }}">
                        @foreach ($series as $s)
                            <span class="chart-bar bar-rise" style="height: {{ $s['values'][$i] ? max(1.5, $s['values'][$i] / $top * 100) : 0 }}%; background: {{ $s['color'] }}"></span>
                        @endforeach
                        <span class="chart-tip {{ $edge }}" aria-hidden="true">
                            <span class="mb-1 block font-semibold text-foreground">{{ $category['full'] }}</span>
                            @foreach ($series as $s)
                                <span class="flex items-center gap-2 text-muted">
                                    <span class="size-2 rounded-[2px]" style="background: {{ $s['color'] }}"></span>{{ $s['label'] }}
                                    <b class="num ml-auto pl-3 font-semibold text-foreground">{{ $format($s['values'][$i]) }}</b>
                                </span>
                            @endforeach
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-2 flex gap-3 text-caption text-muted" aria-hidden="true">
        <span class="w-11 shrink-0"></span>
        <div class="grid flex-1 gap-1 text-center sm:gap-2" style="grid-template-columns: repeat({{ $count }}, minmax(0, 1fr))">
            @foreach ($categories as $category)<span class="truncate">{{ $category['short'] }}</span>@endforeach
        </div>
    </div>

    <details class="group/table mt-4 text-sm">
        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 rounded-md text-caption font-semibold text-muted transition-colors hover:text-foreground [&::-webkit-details-marker]:hidden">
            {{ icon('list', 'size-4') }}<span class="group-open/table:hidden">ສະແດງເປັນຕາຕະລາງ</span><span class="hidden group-open/table:inline">ເຊື່ອງຕາຕະລາງ</span>
        </summary>
        <div class="mt-3 overflow-x-auto rounded-lg border border-border">
            <table class="data-table">
                <caption class="sr-only">{{ $caption }}</caption>
                <thead><tr><th scope="col">ໄລຍະ</th>@foreach ($series as $s)<th scope="col" class="!text-right">{{ $s['label'] }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($categories as $i => $category)
                        <tr><th scope="row" class="!border-b-border !bg-transparent !text-[0.9375rem] !font-medium !text-foreground">{{ $category['full'] }}</th>
                            @foreach ($series as $s)<td class="num text-right">{{ $format($s['values'][$i]) }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
