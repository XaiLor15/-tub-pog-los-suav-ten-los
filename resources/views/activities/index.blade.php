{{-- Activities — artboard "Activities" (list + detail with 5 tabs) --}}
@php
    $e = $selected;
    $editing ??= null;
    $openForm = $canCreate && (request()->boolean('new') || $editing || ($errors->any() && old('_form') === 'event'));
    $keep = request()->only(['q', 'category', 'when']);
@endphp
<x-layouts.app title="ກິດຈະກຳ" :crumbs="['ໜ້າຫຼັກ', 'ກິດຈະກຳ']">
    <x-page-header title="ກິດຈະກຳ" icon="calendar-days" :description="'ປີ '.now()->year.' · '.$yearCount.' ກິດຈະກຳ · ຈະມາເຖິງ '.$upcomingCount">
        @if ($canCreate)
            <x-slot:actions><a href="{{ route('activities.index', ['new' => 1]) }}" class="btn-primary">{{ icon('calendar-plus', 'size-4') }} ສ້າງກິດຈະກຳ</a></x-slot:actions>
        @endif
    </x-page-header>

    <div class="grid items-start gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
        <div class="{{ $e ? 'order-2 lg:order-1' : '' }}">
            <form method="GET" action="{{ route('activities.index') }}" class="flex gap-2">
                <input type="hidden" name="when" value="{{ $when }}">
                <x-search placeholder="ຄົ້ນຫາກິດຈະກຳ" class="flex-1" />
                <div class="w-36"><x-select name="category" id="filter-category" :options="['' => 'ທຸກໝວດ'] + array_combine(\App\Support\Lookups::EVENT_CATEGORIES, \App\Support\Lookups::EVENT_CATEGORIES)" :selected="request('category')" data-autosubmit aria-label="ໝວດ" /></div>
            </form>
            <div class="segmented mt-3 grid grid-cols-2">
                <a href="{{ route('activities.index', array_merge($keep, ['when' => null])) }}" class="segment gap-1.5" @if ($when === 'upcoming') aria-current="page" @endif>{{ icon('calendar-clock', 'size-4') }} ຈະມາເຖິງ</a>
                <a href="{{ route('activities.index', array_merge($keep, ['when' => 'past'])) }}" class="segment gap-1.5" @if ($when === 'past') aria-current="page" @endif>{{ icon('history', 'size-4') }} ຜ່ານມາແລ້ວ</a>
            </div>
            <div class="mt-3 space-y-3" data-stagger>
                @forelse ($events as $ev)
                    @php $isToday = $ev->start_date->isToday(); @endphp
                    <a href="{{ route('activities.show', array_merge(['event' => $ev->id], $keep)) }}" @if ($e?->id === $ev->id) aria-current="true" @endif
                       class="group card card-hover flex gap-4 p-4 aria-[current]:!border-primary aria-[current]:bg-primary-soft/40 aria-[current]:shadow-[0_0_0_3px_var(--ring)]">
                        @include('partials.event-date', ['date' => $ev->start_date])
                        <span class="min-w-0 flex-1">
                            <span class="flex items-start justify-between gap-2">
                                <span class="font-bold leading-snug transition-colors group-hover:text-primary">{{ $ev->title }}</span>
                                @if ($isToday)<x-badge tone="warning" dot class="shrink-0 text-xs">ມື້ນີ້</x-badge>@elseif ($ev->is_public)<x-badge tone="success" class="shrink-0 text-xs">ສາທາລະນະ</x-badge>@endif
                            </span>
                            <span class="mt-0.5 block truncate text-sm text-muted">{{ $ev->category }}{{ $ev->location ? ' · '.$ev->location : '' }}</span>
                            <span class="mt-1 inline-flex items-center gap-1.5 text-[0.8125rem] font-medium">{{ icon('users', 'size-3.5 text-muted') }}{{ $ev->participants_count }} ຄົນເຂົ້າຮ່ວມ</span>
                        </span>
                    </a>
                @empty
                    <div class="card"><x-empty icon="calendar-days" :message="request()->hasAny(['q', 'category']) ? 'ບໍ່ພົບກິດຈະກຳທີ່ກົງກັບຕົວກອງ' : ($when === 'past' ? 'ຍັງບໍ່ມີກິດຈະກຳທີ່ຜ່ານມາ' : 'ຍັງບໍ່ມີກິດຈະກຳ')">
                        @if ($canCreate && $when === 'upcoming')<a href="{{ route('activities.index', ['new' => 1]) }}" class="btn-primary btn-sm">ສ້າງກິດຈະກຳທຳອິດ</a>@endif
                    </x-empty></div>
                @endforelse
                @if ($events->hasPages())<div class="flex justify-center">{{ $events->onEachSide(1)->links() }}</div>@endif
            </div>
        </div>

        <div class="{{ $e ? 'order-1 lg:order-2' : 'hidden lg:block' }}">
            @if ($e)
                @include('activities.detail')
            @else
                <div class="card"><x-empty icon="calendar-days" message="ເລືອກກິດຈະກຳເພື່ອເບິ່ງລາຍລະອຽດ" hint="ລາຍລະອຽດ, ຜູ້ເຂົ້າຮ່ວມ, ການບໍລິຈາກ ແລະ ຮູບພາບ ຈະສະແດງຢູ່ບ່ອນນີ້" /></div>
            @endif
        </div>
    </div>

    @if ($canCreate)
        <x-slot:after>@include('activities.form', ['editing' => $editing, 'open' => $openForm])</x-slot:after>
    @endif
</x-layouts.app>
