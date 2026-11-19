{{-- Dashboard — artboard "Main" --}}
@php
    $user = auth()->user();
    $s = $stats;
    $b = $breakdown;
    $maxGen = max(1, max($b['by_generation'] ?: [0]));
@endphp
<x-layouts.app title="ໜ້າຫຼັກ" :crumbs="['ໜ້າຫຼັກ']">
    <x-page-header :title="$user ? 'ສະບາຍດີ, '.\Illuminate\Support\Str::of($user->member?->name ?? $user->display_name)->explode(' ')->first() : 'ສະບາຍດີ'">
        <x-slot:eyebrow><p class="text-sm text-muted">{{ \App\Support\Format::weekday(now()) }}, {{ lao_date(now()) }}</p></x-slot:eyebrow>
        @can('members.write')
            <x-slot:actions>
                <a href="{{ route('activities.index', ['new' => 1]) }}" class="btn-outline">{{ icon('calendar-plus', 'size-4') }} ເພີ່ມກິດຈະກຳ</a>
                <a href="{{ route('members.index', ['new' => 1]) }}" class="btn-primary">{{ icon('user-round-plus', 'size-4') }} ເພີ່ມສະມາຊິກ</a>
            </x-slot:actions>
        @endcan
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6" data-stagger>
        <x-stat-card label="ສະມາຊິກທັງໝົດ" icon="users" :value="\App\Support\Format::number($s['members'])" :hint="'+'.$s['members_added_30d'].' ໃນ 30 ວັນ'" :hint-tone="$s['members_added_30d'] ? 'success' : null" />
        <x-stat-card label="ຄະນະນຳ" icon="award" :value="$s['leaders']" :hint="$s['term_start'] ? 'ວາລະ '.$s['term_start'].'–'.$s['term_end'] : 'ຍັງບໍ່ກຳນົດວາລະ'" />
        <x-stat-card label="ຜູ້ອາວຸໂສ" icon="book-user" :value="$s['elders']" :hint="'ຜູ້ນຳພິທີ '.$s['elders_rite'].' ທ່ານ'" />
        <x-stat-card label="ຈຳນວນ Phaj" icon="network" :value="$s['generations']" :hint="$s['min_generation'] ? 'Phaj '.$s['min_generation'].' – Phaj '.$s['max_generation'] : '—'" />
        <x-stat-card label="ກິດຈະກຳປີນີ້" icon="calendar-days" :value="$s['events_this_year']" :hint="'ຈະມາເຖິງ '.$s['upcoming_events'].' ລາຍການ'" />
        <x-stat-card label="ຂ່າວສານ" icon="newspaper" :value="$s['news_total']" :hint="'+'.$s['news_added_7d'].' ໃນ 7 ວັນ'" :hint-tone="$s['news_added_7d'] ? 'success' : null" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card card-pad" aria-labelledby="by-phaj" data-bars>
            <div class="mb-4 flex items-center justify-between">
                <h2 id="by-phaj" class="text-[1.0625rem] font-semibold">ສະມາຊິກຕາມ Phaj</h2>
                <a href="{{ route('members.index') }}" class="link text-sm">ເບິ່ງທັງໝົດ</a>
            </div>
            @forelse ($b['by_generation'] as $gen => $count)
                <a href="{{ route('members.index', ['phaj' => $gen]) }}" class="group -mx-2 flex items-center gap-4 rounded-lg px-2 py-1.5 transition-colors hover:bg-surface-2/70">
                    <span class="w-16 shrink-0 text-sm text-muted">Phaj {{ $gen }}</span>
                    <span class="h-2 flex-1 overflow-hidden rounded-full bg-surface-2"><span class="bar-grow block h-full rounded-full bg-gradient-to-r from-primary to-primary-strong" style="width: {{ round($count / $maxGen * 100, 1) }}%"></span></span>
                    <span class="num w-12 shrink-0 text-right text-sm font-semibold">{{ \App\Support\Format::number($count) }}</span>
                </a>
            @empty
                <x-empty icon="users" message="ຍັງບໍ່ມີສະມາຊິກ">
                    @can('members.write')<a href="{{ route('members.index', ['new' => 1]) }}" class="btn-primary btn-sm">ເພີ່ມສະມາຊິກຄົນທຳອິດ</a>@endcan
                </x-empty>
            @endforelse
        </section>

        <section class="card card-pad" aria-labelledby="by-sex">
            <h2 id="by-sex" class="mb-4 text-[1.0625rem] font-semibold">ສະມາຊິກຕາມເພດ ແລະ ສະຖານະ</h2>
            @if ($b['total'])
                @php $mp = $b['male'] / $b['total'] * 100; @endphp
                <div class="bar-grow flex h-3 gap-[2px] overflow-hidden rounded-full" role="img" aria-label="ຊາຍ {{ $b['male'] }}, ຍິງ {{ $b['female'] }}">
                    <span class="bg-chart-1" style="width: {{ $mp }}%"></span><span class="flex-1 bg-chart-2"></span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div><p class="flex items-center gap-2 text-sm text-muted"><span class="size-2.5 rounded-sm bg-chart-1"></span>ຊາຍ</p>
                        <p class="text-xl font-bold"><span class="num">{{ \App\Support\Format::number($b['male']) }}</span> <span class="text-sm font-normal text-muted">{{ percent($mp, 0) }}</span></p></div>
                    <div><p class="flex items-center gap-2 text-sm text-muted"><span class="size-2.5 rounded-sm bg-chart-2"></span>ຍິງ</p>
                        <p class="text-xl font-bold"><span class="num">{{ \App\Support\Format::number($b['female']) }}</span> <span class="text-sm font-normal text-muted">{{ percent(100 - $mp, 0) }}</span></p></div>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-4 border-t border-border pt-4">
                    <div><p class="text-sm text-muted">ມີຊີວິດ</p><p class="num text-xl font-bold">{{ \App\Support\Format::number($b['alive']) }}</p></div>
                    <div><p class="text-sm text-muted">ເສຍຊີວິດແລ້ວ</p><p class="num text-xl font-bold">{{ \App\Support\Format::number($b['dead']) }}</p></div>
                </div>
            @else
                <x-empty icon="users" message="ຍັງບໍ່ມີຂໍ້ມູນສະມາຊິກ" />
            @endif
        </section>
    </div>

    @if ($canFinance)
        <section class="card card-pad mt-6" aria-labelledby="fin6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 id="fin6" class="text-[1.0625rem] font-semibold">ການເງິນ 6 ເດືອນຫຼ້າສຸດ</h2>
                <div class="flex items-center gap-4 text-sm text-muted">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-chart-1"></span>ລາຍຮັບ</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-chart-2"></span>ລາຍຈ່າຍ</span>
                    <a href="{{ route('finance.index') }}" class="link">ໄປທີ່ການເງິນ</a>
                </div>
            </div>
            <div class="mb-6 flex flex-wrap gap-x-10 gap-y-3">
                <div><p class="text-sm text-muted">ຍອດຄົງເຫຼືອ</p><p class="num text-[1.75rem] font-bold">{{ money($balance) }}</p></div>
                <div><p class="text-sm text-muted">ລາຍຮັບເດືອນນີ້</p><p class="num text-xl font-bold">{{ money($thisMonth['total_income']) }}</p></div>
                <div><p class="text-sm text-muted">ລາຍຈ່າຍເດືອນນີ້</p><p class="num text-xl font-bold">{{ money($thisMonth['total_expense']) }}</p></div>
            </div>
            @include('partials.finance-chart', ['monthly' => $monthly])
        </section>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card card-pad" aria-labelledby="up-events">
            <div class="mb-4 flex items-center justify-between">
                <h2 id="up-events" class="text-[1.0625rem] font-semibold">ກິດຈະກຳທີ່ຈະມາເຖິງ</h2>
                <a href="{{ route('activities.index') }}" class="link text-sm">ທັງໝົດ</a>
            </div>
            <ul class="space-y-1">
                @forelse ($events as $event)
                    <li><a href="{{ route('activities.show', $event) }}" class="group flex items-center gap-4 rounded-lg p-2 transition-colors hover:bg-surface-2/70">
                        @include('partials.event-date', ['date' => $event->start_date])
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold">{{ $event->title }}</span>
                            <span class="block truncate text-sm text-muted">{{ $event->start_time ? substr($event->start_time, 0, 5).' · ' : '' }}{{ $event->location }}</span>
                        </span>
                        <x-badge :tone="$event->is_public ? 'success' : 'primary'">{{ $event->is_public ? 'ສາທາລະນະ' : 'ສະມາຊິກ' }}</x-badge>
                    </a></li>
                @empty
                    <x-empty icon="calendar-days" message="ຍັງບໍ່ມີກິດຈະກຳທີ່ຈະມາເຖິງ" />
                @endforelse
            </ul>
        </section>

        <section class="card card-pad" aria-labelledby="latest-news">
            <div class="mb-4 flex items-center justify-between">
                <h2 id="latest-news" class="text-[1.0625rem] font-semibold">ຂ່າວລ່າສຸດ</h2>
                <a href="{{ route('news.index') }}" class="link text-sm">ທັງໝົດ</a>
            </div>
            <ul class="space-y-1">
                @forelse ($news as $item)
                    <li><a href="{{ route('news.show', $item) }}" class="block rounded-lg p-2 transition-colors hover:bg-surface-2/70">
                        <span class="flex items-center gap-2 text-[0.8125rem] text-muted">
                            <x-badge tone="primary" class="text-xs">{{ $item->category }}</x-badge>{{ lao_date($item->published_at) }}
                            @if ($item->is_pinned)<span class="flex items-center gap-1 font-semibold text-warning">{{ icon('pin', 'size-3.5') }} ປັກໝຸດ</span>@endif
                        </span>
                        <span class="mt-1 block font-semibold">{{ $item->title }}</span>
                    </a></li>
                @empty
                    <x-empty icon="newspaper" message="ຍັງບໍ່ມີຂ່າວ" />
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.app>
