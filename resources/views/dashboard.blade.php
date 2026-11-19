{{--
    Dashboard. Reading order: who/when → headline figures → money (members only) → people → what's next.
    All figures come from DashboardController; this view only arranges and formats them.
--}}
@use('App\Support\Format')
@php
    $user = auth()->user();
    $s = $stats;
    $b = $breakdown;
    $firstName = $user ? \Illuminate\Support\Str::of($user->member?->name ?? $user->display_name)->explode(' ')->first() : null;
    $pendingUsers = $user?->isAdmin() ? \App\View\Navigation::pendingCount() : 0;

    $generations = collect($b['by_generation'])->map(fn ($count, $gen) => [
        'label' => 'Phaj '.$gen, 'short' => (string) $gen, 'value' => $count, 'href' => route('members.index', ['phaj' => $gen]),
    ])->values()->all();

    if ($canFinance) {
        $categories = collect($monthly)->map(fn ($m) => ['short' => $m['label'], 'full' => $m['label'].' '.substr($m['month'], 0, 4)])->all();
        $net = $thisMonth['total_income'] - $thisMonth['total_expense'];
        $previous = $monthly[count($monthly) - 2] ?? null;
        $incomeChange = $previous && $previous['income'] ? ($thisMonth['total_income'] - $previous['income']) / $previous['income'] * 100 : null;
    }
@endphp
<x-layouts.app title="ໜ້າຫຼັກ" :crumbs="['ໜ້າຫຼັກ']">
    <x-page-header :title="$firstName ? 'ສະບາຍດີ, '.$firstName : 'ສະບາຍດີ'"
                   :description="'ສະມາຊິກ '.Format::number($s['members']).' ຄົນ ໃນ '.$s['generations'].' Phaj · ກິດຈະກຳທີ່ຈະມາເຖິງ '.$s['upcoming_events'].' ລາຍການ'">
        <x-slot:eyebrow>
            <p class="mb-2 inline-flex items-center gap-1.5 rounded-full border border-border bg-card/70 px-2.5 py-0.5 text-caption font-medium text-muted">
                {{ icon('calendar-days', 'size-3.5') }}{{ Format::weekday(now()) }}, {{ lao_date(now()) }}
            </p>
        </x-slot:eyebrow>
        @can('members.write')
            <x-slot:actions>
                <a href="{{ route('activities.index', ['new' => 1]) }}" class="btn-outline">{{ icon('calendar-plus', 'size-4') }} ເພີ່ມກິດຈະກຳ</a>
                <a href="{{ route('members.index', ['new' => 1]) }}" class="btn-primary">{{ icon('user-round-plus', 'size-4') }} ເພີ່ມສະມາຊິກ</a>
            </x-slot:actions>
        @endcan
    </x-page-header>

    @if ($pendingUsers)
        <x-info-banner icon="user-round-plus" class="mb-6 items-center">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                <p><span class="font-semibold">ມີ {{ $pendingUsers }} ບັນຊີລໍຖ້າອະນຸມັດ.</span> <span class="text-muted">ກວດສອບ ແລະ ເຊື່ອມກັບຂໍ້ມູນສະມາຊິກ.</span></p>
                <a href="{{ route('admin.index') }}" class="btn-outline btn-sm">ໄປອະນຸມັດ {{ icon('arrow-right', 'size-4') }}</a>
            </div>
        </x-info-banner>
    @endif

    {{-- Headline figures --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-6" data-stagger>
        <x-stat-card label="ສະມາຊິກທັງໝົດ" icon="users" :href="route('members.index')" :value="Format::number($s['members'])"
                     :hint="'+'.$s['members_added_30d'].' ໃນ 30 ວັນ'" :hint-tone="$s['members_added_30d'] ? 'success' : null" :trend="$s['members_added_30d'] ? 'up' : null" />
        <x-stat-card label="ຈຳນວນ Phaj" icon="network" :href="route('members.index', ['view' => 'tree'])" :value="$s['generations']"
                     :hint="$s['min_generation'] ? 'Phaj '.$s['min_generation'].' – '.$s['max_generation'] : '—'" />
        <x-stat-card label="ຄະນະນຳ" icon="award" :href="route('leadership.index')" :value="$s['leaders']"
                     :hint="$s['term_start'] ? 'ວາລະ '.$s['term_start'].'–'.$s['term_end'] : 'ຍັງບໍ່ກຳນົດວາລະ'" />
        <x-stat-card label="ຜູ້ອາວຸໂສ" icon="book-user" :href="route('elders.index')" :value="$s['elders']" :hint="'ຜູ້ນຳພິທີ '.$s['elders_rite'].' ທ່ານ'" />
        <x-stat-card label="ກິດຈະກຳປີນີ້" icon="calendar-days" :href="route('activities.index')" :value="$s['events_this_year']" :hint="'ຈະມາເຖິງ '.$s['upcoming_events'].' ລາຍການ'" />
        <x-stat-card label="ຂ່າວສານ" icon="newspaper" :href="route('news.index')" :value="$s['news_total']"
                     :hint="'+'.$s['news_added_7d'].' ໃນ 7 ວັນ'" :hint-tone="$s['news_added_7d'] ? 'success' : null" :trend="$s['news_added_7d'] ? 'up' : null" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-12">
        @if ($canFinance)
            <x-panel id="fin6" title="ການເງິນ" description="ລາຍຮັບ ແລະ ລາຍຈ່າຍ 6 ເດືອນຫຼ້າສຸດ" icon="wallet" :href="route('finance.index')" link-label="ໄປທີ່ການເງິນ" class="xl:col-span-8">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,15rem)_minmax(0,1fr)]">
                    <div class="flex flex-col gap-3">
                        <div class="surface-brand relative overflow-hidden rounded-xl p-5 shadow-lift">
                            <span class="lattice pointer-events-none absolute inset-0 opacity-40" aria-hidden="true"></span>
                            <p class="relative text-sm text-white/75">ຍອດຄົງເຫຼືອ</p>
                            <p class="num relative mt-1 text-[1.625rem] leading-tight font-bold" data-countup="{{ max(0, $balance) }}" data-suffix=" ₭">{{ money($balance) }}</p>
                            <p class="relative mt-2 text-caption text-white/70">ລວມທຸກລາຍການເຖິງມື້ນີ້</p>
                        </div>
                        <dl class="grid grid-cols-2 gap-3 lg:grid-cols-1">
                            <div class="rounded-xl border border-border bg-surface-2/60 p-3.5">
                                <dt class="flex items-center gap-1.5 text-caption text-muted"><span class="size-2 rounded-[2px] bg-chart-1"></span>ລາຍຮັບເດືອນນີ້</dt>
                                <dd class="num mt-0.5 font-bold">{{ money($thisMonth['total_income']) }}</dd>
                                @if ($incomeChange !== null)
                                    <dd class="mt-0.5 flex items-center gap-1 text-[0.75rem] font-medium {{ $incomeChange >= 0 ? 'text-success' : 'text-destructive' }}">
                                        {{ icon($incomeChange >= 0 ? 'trending-up' : 'trending-down', 'size-3.5') }}{{ $incomeChange >= 0 ? '+' : '' }}{{ percent($incomeChange, 0) }} <span class="font-normal text-muted">ທຽບເດືອນກ່ອນ</span>
                                    </dd>
                                @endif
                            </div>
                            <div class="rounded-xl border border-border bg-surface-2/60 p-3.5">
                                <dt class="flex items-center gap-1.5 text-caption text-muted"><span class="size-2 rounded-[2px] bg-chart-2"></span>ລາຍຈ່າຍເດືອນນີ້</dt>
                                <dd class="num mt-0.5 font-bold">{{ money($thisMonth['total_expense']) }}</dd>
                                <dd class="mt-0.5 text-[0.75rem] text-muted">ສຸດທິ <b class="num font-semibold {{ $net >= 0 ? 'text-success' : 'text-destructive' }}">{{ money($net, true) }}</b></dd>
                            </div>
                        </dl>
                    </div>
                    <x-chart.bars caption="ລາຍຮັບ ແລະ ລາຍຈ່າຍ 6 ເດືອນຫຼ້າສຸດ" :categories="$categories" money
                                  :series="[
                                      ['label' => 'ລາຍຮັບ', 'color' => 'var(--chart-1)', 'values' => array_column($monthly, 'income')],
                                      ['label' => 'ລາຍຈ່າຍ', 'color' => 'var(--chart-2)', 'values' => array_column($monthly, 'expense')],
                                  ]" />
                </div>
            </x-panel>
        @else
            <x-panel id="by-phaj" title="ສະມາຊິກຕາມ Phaj" description="ກົດແຕ່ລະຖັນເພື່ອເບິ່ງລາຍຊື່" icon="network" :href="route('members.index')" class="xl:col-span-8">
                @if ($generations)
                    <x-chart.columns caption="ຈຳນວນສະມາຊິກຕາມ Phaj" unit="ຄົນ" :items="$generations" :height="260" />
                @else
                    <x-empty icon="users" message="ຍັງບໍ່ມີສະມາຊິກ" />
                @endif
            </x-panel>
        @endif

        <x-panel id="by-sex" title="ອົງປະກອບສະມາຊິກ" description="ຕາມເພດ ແລະ ສະຖານະ" icon="users" class="xl:col-span-4">
            @if ($b['total'])
                <x-chart.donut caption="ສະມາຊິກຕາມເພດ" center-label="ຄົນ"
                               :segments="[
                                   ['label' => 'ຊາຍ', 'value' => $b['male'], 'color' => 'var(--chart-1)'],
                                   ['label' => 'ຍິງ', 'value' => $b['female'], 'color' => 'var(--chart-2)'],
                               ]" />
                @php $alivePct = $b['alive'] / $b['total'] * 100; @endphp
                <div class="mt-6 border-t border-border pt-5">
                    <div class="mb-2 flex items-baseline justify-between text-sm">
                        <span class="text-muted">ມີຊີວິດ <b class="num ml-1 text-foreground">{{ Format::number($b['alive']) }}</b></span>
                        <span class="text-muted">ເສຍຊີວິດແລ້ວ <b class="num ml-1 text-foreground">{{ Format::number($b['dead']) }}</b></span>
                    </div>
                    <div class="flex h-2 gap-[2px] overflow-hidden rounded-full bg-chart-track" role="img" aria-label="ມີຊີວິດ {{ $b['alive'] }} ຄົນ ({{ percent($alivePct, 0) }}), ເສຍຊີວິດແລ້ວ {{ $b['dead'] }} ຄົນ">
                        <span class="bar-grow rounded-full bg-primary" style="width: {{ $alivePct }}%"></span>
                    </div>
                    <p class="mt-1.5 text-caption text-muted">{{ percent($alivePct, 0) }} ຂອງສະມາຊິກທັງໝົດຍັງມີຊີວິດຢູ່</p>
                </div>
            @else
                <x-empty icon="users" message="ຍັງບໍ່ມີຂໍ້ມູນສະມາຊິກ" />
            @endif
        </x-panel>

        @if ($canFinance)
            <x-panel id="by-phaj" title="ສະມາຊິກຕາມ Phaj" description="ກົດແຕ່ລະຖັນເພື່ອເບິ່ງລາຍຊື່" icon="network" :href="route('members.index')" class="xl:col-span-5">
                @if ($generations)
                    <x-chart.columns caption="ຈຳນວນສະມາຊິກຕາມ Phaj" unit="ຄົນ" :items="$generations" />
                @else
                    <x-empty icon="users" message="ຍັງບໍ່ມີສະມາຊິກ" />
                @endif
            </x-panel>
        @endif

        <x-panel id="up-events" title="ກິດຈະກຳທີ່ຈະມາເຖິງ" icon="calendar-days" :href="route('activities.index')" link-label="ທັງໝົດ" flush class="xl:col-span-7">
            <ul class="divide-y divide-border/70" data-stagger>
                @forelse ($events as $event)
                    <li>
                        <a href="{{ route('activities.show', $event) }}" class="group flex items-center gap-4 rounded-xl p-2.5 transition-colors hover:bg-surface-2/80 focus-visible:bg-surface-2">
                            @include('partials.event-date', ['date' => $event->start_date])
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold transition-colors group-hover:text-primary">{{ $event->title }}</span>
                                <span class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-caption text-muted">
                                    @if ($event->start_time)<span class="inline-flex items-center gap-1">{{ icon('clock', 'size-3.5') }}{{ substr($event->start_time, 0, 5) }}</span>@endif
                                    @if ($event->location)<span class="min-w-0 truncate">{{ $event->location }}</span>@endif
                                    @if ($event->participants_count)<span class="inline-flex items-center gap-1">{{ icon('users', 'size-3.5') }}{{ $event->participants_count }} ຄົນ</span>@endif
                                </span>
                            </span>
                            <x-badge :tone="$event->is_public ? 'success' : 'primary'" class="hidden sm:inline-flex">{{ $event->is_public ? 'ສາທາລະນະ' : 'ສະມາຊິກ' }}</x-badge>
                            {{ icon('chevron-right', 'size-4 shrink-0 text-muted transition-transform group-hover:translate-x-0.5') }}
                        </a>
                    </li>
                @empty
                    <li><x-empty icon="calendar-days" message="ຍັງບໍ່ມີກິດຈະກຳທີ່ຈະມາເຖິງ" hint="ກິດຈະກຳໃໝ່ຈະສະແດງຢູ່ບ່ອນນີ້" /></li>
                @endforelse
            </ul>
        </x-panel>

        <x-panel id="latest-news" title="ຂ່າວລ່າສຸດ" icon="newspaper" :href="route('news.index')" link-label="ທັງໝົດ" flush :class="$canFinance ? 'xl:col-span-12' : 'xl:col-span-5'">
            <ul @class(['grid gap-3 px-2 pb-2 sm:grid-cols-2 xl:grid-cols-3' => $canFinance, 'divide-y divide-border/70' => ! $canFinance]) data-stagger>
                @forelse ($news as $item)
                    <li>
                        <a href="{{ route('news.show', $item) }}" @class(['group block h-full rounded-xl p-2.5 transition-colors hover:bg-surface-2/80 focus-visible:bg-surface-2', 'border border-border !p-4 card-hover hover:!bg-card' => $canFinance])>
                            <span class="flex flex-wrap items-center gap-2 text-caption text-muted">
                                <x-badge tone="primary" class="text-xs">{{ $item->category }}</x-badge>
                                <span>{{ Format::ago($item->published_at) }}</span>
                                @if ($item->is_pinned)<span class="inline-flex items-center gap-1 font-semibold text-warning">{{ icon('pin', 'size-3.5') }} ປັກໝຸດ</span>@endif
                            </span>
                            <span class="mt-1 line-clamp-2 block font-semibold transition-colors group-hover:text-primary">{{ $item->title }}</span>
                        </a>
                    </li>
                @empty
                    <li><x-empty icon="newspaper" message="ຍັງບໍ່ມີຂ່າວ" /></li>
                @endforelse
            </ul>
        </x-panel>

    </div>
</x-layouts.app>
