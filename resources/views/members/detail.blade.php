{{-- DetailSheet for one member: info, paternal line, siblings (reorder/unlink), children, spouses. --}}
@php
    $priv = $private;
    $grand = $m->father?->father;
@endphp
<section class="card" aria-label="ລາຍລະອຽດ {{ $m->name }}">
    <div class="flex items-start gap-4 border-b border-border p-5">
        <x-avatar :member="$m" size="lg" />
        <div class="min-w-0 flex-1">
            <h2 class="text-[1.375rem] leading-tight">{{ $m->name }}</h2>
            @if ($m->hmong_name)<p class="text-sm text-muted">{{ $m->hmong_name }}</p>@endif
            <div class="mt-2 flex flex-wrap gap-1.5">
                <x-phaj :n="$m->generation" />
                <x-badge tone="neutral">{{ $m->childLabel() }}</x-badge>
                <x-badge :tone="$m->is_alive ? 'success' : 'neutral'">{{ $m->is_alive ? 'ມີຊີວິດ' : 'ເສຍຊີວິດແລ້ວ' }}</x-badge>
            </div>
        </div>
        <div class="flex flex-col gap-2">
            <a href="{{ route('members.index') }}" class="icon-btn-sm" aria-label="ປິດ">{{ icon('x', 'size-4') }}</a>
            @can('members.write')
                <a href="{{ route('members.index', ['edit' => $m->id]) }}" class="icon-btn-sm" aria-label="ແກ້ໄຂ">{{ icon('pencil', 'size-4') }}</a>
            @endcan
        </div>
    </div>

    <dl class="grid grid-cols-[6rem_1fr] gap-y-2 p-5 text-[0.9375rem]">
        <dt class="text-muted">ວັນເກີດ</dt><dd>{{ \App\Support\MemberPresenter::birthText($m, $priv) }}</dd>
        @unless ($m->is_alive)<dt class="text-muted">ປີເສຍຊີວິດ</dt><dd>{{ $m->death_year ?? '—' }}</dd>@endunless
        <dt class="text-muted">ແຂວງ</dt><dd>{{ $m->province ?? '—' }}</dd>
        <dt class="text-muted">ບ້ານ</dt><dd>@if ($priv){{ $m->village ?? '—' }}@else<x-locked />@endif</dd>
        <dt class="text-muted">ເບີໂທ</dt><dd class="num">@if ($priv){{ phone_display($m->phone) }}@else<x-locked />@endif</dd>
    </dl>

    <div class="border-t border-border p-5">
        <div class="mb-3 flex items-center justify-between">
            <h3 class="font-semibold">ສາຍພໍ່</h3>
            <a href="{{ route('members.index', ['view' => 'tree', 'id' => $m->id]) }}" class="link text-sm">ເບິ່ງສາຍບັນພະບຸລຸດ →</a>
        </div>
        @if ($m->father)
            <div class="divide-y divide-border rounded-lg border border-border">
                @foreach (array_filter(['ພໍ່' => $m->father, 'ປູ່' => $grand]) as $rel => $p)
                    <a href="{{ route('members.show', $p) }}" class="flex items-center gap-3 px-3 py-2 hover:bg-surface-2/60">
                        <span class="w-8 text-sm text-muted">{{ $rel }}</span><x-avatar :member="$p" size="xs" /><span class="flex-1 font-medium">{{ $p->name }}</span><x-phaj :n="$p->generation" />
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-muted">ຕົ້ນຕະກູນ — ບໍ່ມີຂໍ້ມູນພໍ່</p>
        @endif
    </div>

    @if ($m->father)
        <div class="border-t border-border p-5">
            <h3 class="mb-3 font-semibold">ອ້າຍນ້ອງ <span class="font-normal text-muted">· ລູກຂອງ {{ $m->father->name }} {{ $siblings->count() }} ຄົນ</span></h3>
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
                <a href="{{ route('members.index', ['new' => 1, 'father' => $m->father_id]) }}" class="btn-outline btn-sm mt-3 border-dashed">+ ເພີ່ມອ້າຍນ້ອງ (ລູກຂອງ {{ $m->father->name }})</a>
                <p class="mt-2 text-xs text-muted">↑↓ ປັບລຳດັບການເກີດ · ✕ ເອົາອອກຈາກຄອບຄົວ (ບໍ່ລຶບສະມາຊິກ)</p>
            @endcan
        </div>
    @endif

    <div class="border-t border-border p-5">
        <h3 class="mb-2 font-semibold">ລູກ <span class="font-normal text-muted">· {{ $children->count() }} ຄົນ</span></h3>
        @forelse ($children as $c)
            <a href="{{ route('members.show', $c) }}" class="flex items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-surface-2/60">
                <span class="inline-flex size-6 items-center justify-center rounded-md bg-surface-2 text-xs font-bold">{{ $c->birth_order }}</span>
                <x-avatar :member="$c" size="xs" /><span class="flex-1">{{ $c->name }}</span><span class="text-xs text-muted">{{ $c->sex === 'female' ? 'ລູກສາວ' : 'ລູກຊາຍ' }}</span>
            </a>
        @empty
            <p class="text-sm text-muted">ຍັງບໍ່ມີລູກໃນລະບົບ</p>
        @endforelse
        @if ($m->sex === 'male')
            @can('members.write')
                <a href="{{ route('members.index', ['new' => 1, 'father' => $m->id]) }}" class="btn-outline btn-sm mt-3">+ ເພີ່ມລູກ</a>
            @endcan
        @endif
    </div>

    @if ($m->spouses->isNotEmpty())
        <div class="border-t border-border p-5">
            <h3 class="mb-2 font-semibold">ຄູ່ສົມລົດ</h3>
            @foreach ($m->spouses as $sp)
                <a href="{{ route('members.show', $sp->related_member_id) }}" class="flex items-center gap-3 py-1"><x-avatar :member="$sp->related" size="xs" />{{ $sp->related?->name }}</a>
            @endforeach
            <p class="mt-1 text-xs text-muted">ຂໍ້ມູນຄູ່ສົມລົດເປັນແບບອ່ານຢ່າງດຽວ</p>
        </div>
    @endif

    @can('members.delete')
        <div class="border-t border-border p-5">
            <form method="POST" action="{{ route('members.destroy', $m) }}" data-confirm="ລຶບ {{ $m->name }}? ຂໍ້ມູນຈະຖືກເກັບປະຫວັດໄວ້ (Soft delete) ແລະ ບັນທຶກໃນ Audit log." data-confirm-danger data-confirm-label="ລຶບ">
                @csrf @method('DELETE')
                <button class="btn-danger-outline btn-sm w-full">{{ icon('trash-2', 'size-4') }} ລຶບສະມາຊິກ</button>
            </form>
        </div>
    @endcan
</section>
