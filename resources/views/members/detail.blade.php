{{-- DetailSheet for one member: info, paternal line, siblings (reorder/unlink), children, spouses. --}}
@php
    $priv = $private;
    $grand = $m->father?->father;
@endphp
<section class="card overflow-hidden" aria-label="ລາຍລະອຽດ {{ $m->name }}">
    <x-profile-hero :title="$m->name" :subtitle="$m->hmong_name">
        <x-slot:avatar><x-avatar :member="$m" size="xl" /></x-slot:avatar>
        <x-slot:badges>
            <x-phaj :n="$m->generation" />
            <x-badge tone="neutral">{{ $m->childLabel() }}</x-badge>
            <x-badge :tone="$m->is_alive ? 'success' : 'neutral'" dot>{{ $m->is_alive ? 'ມີຊີວິດ' : 'ເສຍຊີວິດແລ້ວ' }}</x-badge>
        </x-slot:badges>
        <x-slot:actions>
            @can('members.write')
                <a href="{{ route('members.index', ['edit' => $m->id]) }}" class="band-btn" aria-label="ແກ້ໄຂ {{ $m->name }}">{{ icon('pencil', 'size-4') }}</a>
            @endcan
            <a href="{{ route('members.index') }}" class="band-btn" aria-label="ປິດ">{{ icon('x', 'size-4') }}</a>
        </x-slot:actions>
    </x-profile-hero>

    <dl class="kv px-5 pb-5 sm:px-6">
        <dt>{{ icon('cake') }}ວັນເກີດ</dt><dd>{{ \App\Support\MemberPresenter::birthText($m, $priv) }}</dd>
        @unless ($m->is_alive)<dt>{{ icon('calendar-days') }}ປີເສຍຊີວິດ</dt><dd>{{ $m->death_year ?? '—' }}</dd>@endunless
        <dt>{{ icon('map-pin') }}ແຂວງ</dt><dd>{{ $m->province ?? '—' }}</dd>
        <dt>{{ icon('house') }}ບ້ານ</dt><dd>@if ($priv){{ $m->village ?? '—' }}@else<x-locked />@endif</dd>
        <dt>{{ icon('phone') }}ເບີໂທ</dt><dd class="num">@if ($priv){{ phone_display($m->phone) }}@else<x-locked />@endif</dd>
    </dl>

    <div class="detail-section">
        <div class="detail-title">
            <h3>ສາຍພໍ່</h3>
            <a href="{{ route('members.index', ['view' => 'tree', 'id' => $m->id]) }}" class="group inline-flex items-center gap-1 text-sm font-semibold text-primary">ເບິ່ງໃນແຜນຜັງ {{ icon('arrow-right', 'size-4 transition-transform group-hover:translate-x-0.5') }}</a>
        </div>
        @if ($m->father)
            <div class="divide-y divide-border rounded-lg border border-border">
                @foreach (array_filter(['ພໍ່' => $m->father, 'ປູ່' => $grand]) as $rel => $p)
                    <a href="{{ route('members.show', $p) }}" class="flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-surface-2/60">
                        <span class="w-8 text-sm text-muted">{{ $rel }}</span><x-avatar :member="$p" size="xs" /><span class="flex-1 font-medium">{{ $p->name }}</span><x-phaj :n="$p->generation" />
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-muted">ຕົ້ນຕະກູນ — ບໍ່ມີຂໍ້ມູນພໍ່</p>
        @endif
    </div>

    @if ($m->father)
        <div class="detail-section">
            <h3 class="detail-title justify-start">ອ້າຍນ້ອງ <span class="font-normal text-muted">· ລູກຂອງ {{ $m->father->name }} {{ $siblings->count() }} ຄົນ</span></h3>
            <ol class="divide-y divide-border rounded-lg border border-border">
                @foreach ($siblings as $i => $s)
                    @php $self = $s->id === $m->id; $rel = $m->siblingLabelFor($s); @endphp
                    <li class="flex items-center gap-2 px-3 py-2 {{ $self ? 'bg-primary-soft/50' : '' }}">
                        <span class="inline-flex size-6 items-center justify-center rounded-md bg-surface-2 text-xs font-bold">{{ $s->birth_order ?? '–' }}</span>
                        <a href="{{ route('members.show', $s) }}" class="min-w-0 flex-1 leading-tight">
                            <span class="block truncate font-semibold">{{ $s->name }}</span>
                            <span class="block text-xs text-muted">{{ $s->sex === 'female' ? 'ລູກສາວ' : 'ລູກຊາຍ' }}{{ $s->birthYear() ? ' · ເກີດ '.$s->birthYear() : '' }}</span>
                        </a>
                        <x-badge :tone="$self ? 'primary' : ($s->sex === 'female' ? 'success' : 'warning')" class="{{ $self ? '!bg-primary !text-primary-foreground' : '' }} text-xs">{{ $rel }}</x-badge>
                        @can('members.write')
                            <form method="POST" action="{{ route('members.move', $s) }}">@csrf<input type="hidden" name="direction" value="up">
                                <button class="icon-btn-sm size-8" @disabled($i === 0) aria-label="ຍ້າຍ {{ $s->name }} ຂຶ້ນ">{{ icon('arrow-up', 'size-3.5') }}</button></form>
                            <form method="POST" action="{{ route('members.move', $s) }}">@csrf<input type="hidden" name="direction" value="down">
                                <button class="icon-btn-sm size-8" @disabled($loop->last) aria-label="ຍ້າຍ {{ $s->name }} ລົງ">{{ icon('arrow-down', 'size-3.5') }}</button></form>
                            <form method="POST" action="{{ route('members.unlink', $s) }}" data-confirm="ເອົາ {{ $s->name }} ອອກຈາກສາຍພໍ່ {{ $m->father->name }}? ສະມາຊິກຈະບໍ່ຖືກລຶບ." data-confirm-danger data-confirm-label="ເອົາອອກ">@csrf
                                <button class="icon-btn-sm size-8 text-destructive" aria-label="ເອົາ {{ $s->name }} ອອກຈາກສາຍພໍ່">{{ icon('x', 'size-3.5') }}</button></form>
                        @endcan
                    </li>
                @endforeach
            </ol>
            @can('members.write')
                <a href="{{ route('members.index', ['new' => 1, 'father' => $m->father_id]) }}" class="btn-outline btn-sm mt-3 border-dashed">{{ icon('plus', 'size-4') }} ເພີ່ມອ້າຍນ້ອງ (ລູກຂອງ {{ $m->father->name }})</a>
                <p class="mt-2 text-xs text-muted">↑↓ ປັບລຳດັບການເກີດ · ✕ ເອົາອອກຈາກຄອບຄົວ (ບໍ່ລຶບສະມາຊິກ)</p>
            @endcan
        </div>
    @endif

    <div class="detail-section">
        <h3 class="detail-title justify-start">ລູກ <span class="font-normal text-muted">· {{ $children->count() }} ຄົນ</span></h3>
        @forelse ($children as $c)
            <a href="{{ route('members.show', $c) }}" class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-surface-2/60">
                <span class="inline-flex size-6 items-center justify-center rounded-md bg-surface-2 text-xs font-bold">{{ $c->birth_order }}</span>
                <x-avatar :member="$c" size="xs" /><span class="flex-1">{{ $c->name }}</span><span class="text-xs text-muted">{{ $c->sex === 'female' ? 'ລູກສາວ' : 'ລູກຊາຍ' }}</span>
            </a>
        @empty
            <p class="text-sm text-muted">ຍັງບໍ່ມີລູກໃນລະບົບ</p>
        @endforelse
        @if ($m->sex === 'male')
            @can('members.write')
                <a href="{{ route('members.index', ['new' => 1, 'father' => $m->id]) }}" class="btn-outline btn-sm mt-3">{{ icon('plus', 'size-4') }} ເພີ່ມລູກ</a>
            @endcan
        @endif
    </div>

    @if ($m->spouses->isNotEmpty())
        <div class="detail-section">
            <h3 class="detail-title justify-start">ຄູ່ສົມລົດ</h3>
            @foreach ($m->spouses as $sp)
                <a href="{{ route('members.show', $sp->related_member_id) }}" class="flex items-center gap-3 py-1"><x-avatar :member="$sp->related" size="xs" />{{ $sp->related?->name }}</a>
            @endforeach
            <p class="mt-1 text-xs text-muted">ຂໍ້ມູນຄູ່ສົມລົດເປັນແບບອ່ານຢ່າງດຽວ</p>
        </div>
    @endif

    @can('members.delete')
        <div class="detail-section bg-surface-2/40">
            <form method="POST" action="{{ route('members.destroy', $m) }}" data-confirm="ລຶບ {{ $m->name }}? ຂໍ້ມູນຈະຖືກເກັບປະຫວັດໄວ້ (Soft delete) ແລະ ບັນທຶກໃນ Audit log." data-confirm-danger data-confirm-label="ລຶບ">
                @csrf @method('DELETE')
                <button class="btn-danger-outline btn-sm w-full">{{ icon('trash-2', 'size-4') }} ລຶບສະມາຊິກ</button>
            </form>
        </div>
    @endcan
</section>
