{{-- Members — artboard "Members" (table + DetailSheet) --}}
@php
    $genChips = collect([['value' => '', 'label' => 'ທັງໝົດ']])->merge($generations->map(fn ($g) => ['value' => (string) $g, 'label' => 'Phaj '.$g]))->all();
@endphp
<x-layouts.app title="ສະມາຊິກ" :crumbs="['ໜ້າຫຼັກ', 'ສະມາຊິກ']">
    <x-page-header title="ສະມາຊິກ" :description="'ທັງໝົດ '.\App\Support\Format::number($total).' ຄົນ ໃນ '.$generations->count().' Phaj · ເຊື່ອມໂຍງຕາມສາຍພໍ່'">
        <x-slot:actions>
            <div class="segmented">
                <a href="{{ route('members.index') }}" class="segment" aria-current="page">ຕາຕະລາງ</a>
                <a href="{{ route('members.index', ['view' => 'tree']) }}" class="segment">ແຜນຜັງ</a>
            </div>
            @can('view-private')
                <a href="{{ route('members.export', request()->only(['q', 'phaj', 'sex', 'status'])) }}" class="btn-outline">{{ icon('download', 'size-4') }} Export CSV</a>
            @endcan
            @if ($canWrite)
                <a href="{{ route('members.index', ['new' => 1]) }}" class="btn-primary">{{ icon('plus', 'size-4') }} ເພີ່ມສະມາຊິກ</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="GET" action="{{ route('members.index') }}" class="card mb-4 flex flex-col gap-3 p-3 lg:flex-row lg:items-center">
        <label class="relative min-w-0 lg:w-80">
            <span class="sr-only">ຄົ້ນຫາ</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">{{ icon('search', 'size-4') }}</span>
            <input name="q" value="{{ request('q') }}" class="input pl-9" placeholder="ຄົ້ນຫາຊື່ ຫຼື ຊື່ມົ້ງ">
        </label>
        @foreach (request()->only(['phaj']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
        <x-chips param="phaj" :options="$genChips" :current="request('phaj', '')" label="ກອງຕາມ Phaj" class="flex-1" />
        <div class="flex gap-2">
            <div class="w-36"><x-select name="sex" :options="['' => 'ທຸກເພດ', 'male' => 'ຊາຍ', 'female' => 'ຍິງ']" :selected="request('sex')" data-autosubmit aria-label="ເພດ" /></div>
            <div class="w-40"><x-select name="status" :options="['' => 'ທຸກສະຖານະ', 'alive' => 'ມີຊີວິດ', 'dead' => 'ເສຍຊີວິດແລ້ວ']" :selected="request('status')" data-autosubmit aria-label="ສະຖານະ" /></div>
        </div>
    </form>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
        <div class="card overflow-hidden {{ $selected ? 'order-2 xl:order-1' : '' }}">
            @if ($members->isEmpty())
                @if (request()->hasAny(['q', 'phaj', 'sex', 'status']))
                    <x-empty icon="search" message="ບໍ່ພົບສະມາຊິກທີ່ກົງກັບຕົວກອງ{{ request('phaj') ? ' ໃນ Phaj '.request('phaj') : '' }}"><a href="{{ route('members.index') }}" class="btn-outline btn-sm">ລ້າງຕົວກອງ</a></x-empty>
                @else
                    <x-empty icon="users" message="ຍັງບໍ່ມີສະມາຊິກ">@if ($canWrite)<a href="{{ route('members.index', ['new' => 1]) }}" class="btn-primary btn-sm">ເພີ່ມສະມາຊິກຄົນທຳອິດ</a>@endif</x-empty>
                @endif
            @else
                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="data-table">
                        <thead><tr><th>ຊື່</th><th>Phaj</th><th>ພໍ່</th><th>ລຳດັບລູກ</th><th>ແຂວງ</th><th>ເບີໂທ</th></tr></thead>
                        <tbody>
                        @foreach ($members as $m)
                            <tr data-href="{{ route('members.show', array_merge(['member' => $m->id], request()->only(['q', 'phaj', 'sex', 'status', 'page']))) }}" @if ($selected?->id === $m->id) aria-selected="true" @endif>
                                <td>
                                    <a href="{{ route('members.show', array_merge(['member' => $m->id], request()->only(['q', 'phaj', 'sex', 'status', 'page']))) }}" class="flex items-center gap-3">
                                        <x-avatar :member="$m" size="sm" />
                                        <span class="leading-tight"><span class="block font-semibold">{{ $m->name }}</span>
                                        <span class="block text-[0.8125rem] whitespace-nowrap text-muted">{{ $m->sexLabel() }} · {{ $m->is_alive ? \App\Support\MemberPresenter::birthText($m, $private) : $m->lifeSpan() }}</span></span>
                                    </a>
                                </td>
                                <td><x-phaj :n="$m->generation" /></td>
                                <td class="whitespace-nowrap">{{ $m->father?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $m->childLabel() }}</td>
                                <td class="whitespace-nowrap">{{ $m->province ?? '—' }}</td>
                                <td class="num whitespace-nowrap">@if ($private){{ phone_display($m->phone) }}@else<x-locked />@endif</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Mobile cards --}}
                <ul class="divide-y divide-border md:hidden">
                    @foreach ($members as $m)
                        <li><a href="{{ route('members.show', $m) }}" class="flex items-center gap-3 px-4 py-3">
                            <x-avatar :member="$m" size="md" />
                            <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $m->name }}</span>
                            <span class="block text-[0.8125rem] text-muted">{{ $m->childLabel() }}{{ $m->father ? ' · '.$m->father->name : '' }}</span></span>
                            <x-phaj :n="$m->generation" />
                        </a></li>
                    @endforeach
                </ul>
                <x-table-footer :paginator="$members" />
            @endif
        </div>

        @if ($selected)
            <div class="order-1 xl:order-2 xl:sticky xl:top-24">
                @include('members.detail', ['m' => $selected])
            </div>
        @endif
    </div>

    @if ($canWrite)
        <x-slot:after>
            @include('members.form', ['editing' => $editing, 'open' => $openForm || (old('_form') === 'member')])
        </x-slot:after>
    @endif
</x-layouts.app>
