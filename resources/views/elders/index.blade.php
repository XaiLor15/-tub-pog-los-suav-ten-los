{{-- Elders — artboard "Elders" (cards + DetailSheet) --}}
@php
    $chips = collect([['value' => '', 'label' => 'ທັງໝົດ', 'count' => $total]])
        ->merge(collect(\App\Support\Lookups::ELDER_CATEGORIES)->map(fn ($l, $k) => ['value' => $k, 'label' => $l, 'count' => $counts[$k] ?? 0])->values())->all();
    $editing = request('edit') && $canManage ? \App\Models\Elder::with(['member', 'successors'])->find(request('edit')) : null;
    $openForm = $canManage && (request()->boolean('new') || $editing || ($errors->any() && old('_form') === 'elder'));
@endphp
<x-layouts.app title="ຜູ້ອາວຸໂສ" :crumbs="['ຕະກູນ', 'ຜູ້ອາວຸໂສ']">
    <x-page-header title="ຜູ້ອາວຸໂສ" description="ຜູ້ຮັກສາຮີດຄອງ ແລະ ຄວາມຮູ້ດັ້ງເດີມຂອງຕະກູນ">
        <x-slot:actions>
            @can('view-private')<a href="{{ route('elders.export') }}" class="btn-outline">{{ icon('download', 'size-4') }} Export CSV</a>@endcan
            @if ($canManage)<a href="{{ route('elders.index', ['new' => 1]) }}" class="btn-primary">{{ icon('plus', 'size-4') }} ເພີ່ມຜູ້ອາວຸໂສ</a>@endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="ຜູ້ອາວຸໂສທັງໝົດ" :value="$total" :hint="$categories.' ບົດບາດ'" />
        <x-stat-card label="ຍັງມີຊີວິດ" :value="$alive" :hint="$avgAge ? 'ອາຍຸສະເລ່ຍ '.$avgAge.' ປີ' : '—'" />
        <x-stat-card label="ເສຍຊີວິດແລ້ວ" :value="$total - $alive" hint="ບັນທຶກໄວ້ເພື່ອລະນຶກ" />
        <x-stat-card label="ຜູ້ສືບທອດ" :value="$successors" :hint="$withoutSuccessor ? $withoutSuccessor.' ທ່ານຍັງບໍ່ມີຜູ້ສືບທອດ' : 'ທຸກທ່ານມີຜູ້ສືບທອດ'" :hint-tone="$withoutSuccessor ? 'warning' : null" />
    </div>

    <form method="GET" class="card mt-6 flex flex-col gap-3 p-3 lg:flex-row lg:items-center">
        <input name="q" value="{{ request('q') }}" class="input lg:w-72" placeholder="ຄົ້ນຫາຊື່" aria-label="ຄົ້ນຫາ">
        @if (request('cat'))<input type="hidden" name="cat" value="{{ request('cat') }}">@endif
        <x-chips param="cat" :options="$chips" :current="request('cat', '')" label="ບົດບາດ" />
    </form>

    <div class="mt-4 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="{{ $selected ? 'order-2 xl:order-1' : '' }}">
            @if ($elders->isEmpty())
                <div class="card"><x-empty icon="user" :message="request()->hasAny(['q', 'cat']) ? 'ບໍ່ພົບຜູ້ອາວຸໂສທີ່ກົງກັບຕົວກອງ' : 'ຍັງບໍ່ມີຂໍ້ມູນຜູ້ອາວຸໂສ'" /></div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    @foreach ($elders as $e)
                        @php $m = $e->member; @endphp
                        <a href="{{ route('elders.show', array_merge(['elder' => $e->id], request()->only(['q', 'cat']))) }}"
                           class="card flex flex-col items-center p-5 text-center transition hover:border-input {{ $selected?->id === $e->id ? 'border-2 !border-primary bg-primary-soft/40' : '' }}">
                            <x-avatar :member="$m" :url="$e->photoUrl()" size="xl" />
                            <p class="mt-3 text-[1.0625rem] font-bold">{{ $m?->name }}</p>
                            <x-badge tone="primary" class="mt-1">{{ $e->role_title }}</x-badge>
                            <p class="mt-2 text-sm text-muted">Phaj {{ $m?->generation }} · {{ $m?->is_alive ? ($m->age() ? 'ອາຍຸ '.$m->age().' ປີ' : '—') : $m?->lifeSpan() }}</p>
                            @unless ($m?->is_alive)<x-badge class="mt-2">ເສຍຊີວິດແລ້ວ</x-badge>@endunless
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($selected)
            @php $m = $selected->member; @endphp
            <section class="card order-1 p-5 xl:order-2 xl:sticky xl:top-24" aria-label="ລາຍລະອຽດ {{ $m?->name }}">
                <div class="flex items-start gap-4">
                    <x-avatar :member="$m" :url="$selected->photoUrl()" size="xl" />
                    <div class="min-w-0 flex-1">
                        <h2 class="text-[1.375rem]">{{ $m?->name }}</h2>
                        <div class="mt-1 flex flex-wrap gap-1.5"><x-badge tone="primary">{{ $selected->role_title }}</x-badge><x-badge :tone="$m?->is_alive ? 'success' : 'neutral'">{{ $m?->is_alive ? 'ມີຊີວິດ' : 'ເສຍຊີວິດແລ້ວ' }}</x-badge></div>
                    </div>
                    <a href="{{ route('elders.index') }}" class="icon-btn-sm" aria-label="ປິດ">{{ icon('x', 'size-4') }}</a>
                </div>
                <dl class="mt-5 grid grid-cols-[5.5rem_1fr] gap-y-2">
                    <dt class="text-muted">Phaj</dt><dd>Phaj {{ $m?->generation }}</dd>
                    <dt class="text-muted">ອາຍຸ</dt><dd>{{ $m?->age() ? $m->age().' ປີ' : '—' }}{{ $m?->birthYear() ? ' (ເກີດ '.$m->birthYear().')' : '' }}</dd>
                    <dt class="text-muted">ໝວດ</dt><dd>{{ $selected->categoryLabel() }}</dd>
                    <dt class="text-muted">ເບີໂທ</dt><dd class="num">@if ($private){{ phone_display($m?->phone) }}@else<x-locked />@endif</dd>
                </dl>
                <h3 class="mt-5 font-semibold">ປະຫວັດຫຍໍ້</h3>
                <p class="mt-1 text-[0.9375rem] whitespace-pre-line">{{ $selected->bio ?: '—' }}</p>
                <h3 class="mt-5 font-semibold">ຄວາມຊຳນານ</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse ($selected->skills ?? [] as $skill)<span class="rounded-full border border-border px-3 py-1 text-sm">{{ $skill }}</span>@empty<span class="text-sm text-muted">—</span>@endforelse
                </div>
                <h3 class="mt-5 font-semibold">ຜູ້ສືບທອດ</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse ($selected->successors as $s)
                        <a href="{{ route('members.show', $s) }}" class="inline-flex items-center gap-2 rounded-full border border-border py-1 pr-3 pl-1 text-sm hover:bg-surface-2"><x-avatar :member="$s" size="xs" />{{ $s->name }}</a>
                    @empty
                        <span class="text-sm text-warning">ຍັງບໍ່ມີຜູ້ສືບທອດ</span>
                    @endforelse
                </div>
                <div class="mt-6 grid grid-cols-2 gap-2">
                    <a href="{{ route('members.show', $selected->member_id) }}" class="btn-outline">ເບິ່ງໃນລາຍຊື່ສະມາຊິກ</a>
                    <a href="{{ route('members.index', ['view' => 'tree', 'id' => $selected->member_id]) }}" class="btn-outline">ເບິ່ງໃນແຜນຜັງ</a>
                    @if ($canManage)
                        <a href="{{ route('elders.show', ['elder' => $selected->id, 'edit' => $selected->id]) }}" class="btn-primary col-span-2">ແກ້ໄຂຂໍ້ມູນ</a>
                        <form method="POST" action="{{ route('elders.destroy', $selected) }}" class="col-span-2" data-confirm="ເອົາ {{ $m?->name }} ອອກຈາກລາຍຊື່ຜູ້ອາວຸໂສ? ຂໍ້ມູນສະມາຊິກຈະບໍ່ຖືກລຶບ." data-confirm-danger data-confirm-label="ເອົາອອກ">
                            @csrf @method('DELETE')<button class="btn-danger-outline btn-sm w-full">ເອົາອອກຈາກຜູ້ອາວຸໂສ</button></form>
                    @endif
                </div>
            </section>
        @endif
    </div>

    @if ($canManage)
        <x-slot:after>
            <x-dialog id="elder-form" :title="$editing ? 'ແກ້ໄຂຜູ້ອາວຸໂສ' : 'ເພີ່ມຜູ້ອາວຸໂສ'" :open="$openForm">
                <form method="POST" action="{{ $editing ? route('elders.update', $editing) : route('elders.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col" data-busy>
                    @csrf @if ($editing) @method('PUT') @endif <input type="hidden" name="_form" value="elder">
                    <div class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
                        <x-field name="member_id" label="ສະມາຊິກ" required>
                            <div data-member-picker>
                                <input type="hidden" name="member_id" value="{{ old('member_id', $editing?->member_id) }}" data-picker-value>
                                <input name="member_name" value="{{ old('member_name', $editing?->member?->name) }}" class="input @error('member_id') input-error @enderror" placeholder="ພິມຊື່ເພື່ອຄົ້ນຫາ..." data-picker-input>
                            </div>
                        </x-field>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-field name="role_title" label="ບົດບາດ" required><input id="f-role_title" name="role_title" value="{{ old('role_title', $editing?->role_title) }}" class="input" placeholder="ເຊັ່ນ ຜູ້ນຳພິທີສູ່ຂວັນ"></x-field>
                            <x-field name="role_category" label="ໝວດ" required><x-select name="role_category" :options="\App\Support\Lookups::ELDER_CATEGORIES" :selected="old('role_category', $editing?->role_category)" /></x-field>
                        </div>
                        <x-field name="bio" label="ປະຫວັດຫຍໍ້"><textarea id="f-bio" name="bio" rows="4" maxlength="2000" class="textarea">{{ old('bio', $editing?->bio) }}</textarea></x-field>
                        <x-field name="skills" label="ຄວາມຊຳນານ" hint="ແຍກດ້ວຍເຄື່ອງໝາຍຈຸດ (,)"><input id="f-skills" name="skills" value="{{ old('skills', implode(', ', $editing?->skills ?? [])) }}" class="input" placeholder="ສູ່ຂວັນ, ພິທີປີໃໝ່"></x-field>
                        <div>
                            <span class="label">ຜູ້ສືບທອດ</span>
                            <div data-member-picker data-multi="successors"><input class="input" placeholder="ພິມຊື່ເພື່ອເພີ່ມຜູ້ສືບທອດ..." data-picker-input></div>
                            <div class="mt-2 flex flex-wrap gap-2" data-picker-chips>
                                @foreach ($editing?->successors ?? [] as $s)
                                    <span class="inline-flex items-center gap-1 rounded-full border border-border bg-card py-1 pr-1 pl-3 text-sm">{{ $s->name }}<input type="hidden" name="successors[]" value="{{ $s->id }}"><button type="button" class="inline-flex size-6 items-center justify-center rounded-full hover:bg-surface-2" aria-label="ເອົາອອກ">×</button></span>
                                @endforeach
                            </div>
                        </div>
                        <x-field name="photo" label="ຮູບ (ທາງເລືອກ)"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="input py-2" data-preview="elder-photo"><p id="elder-photo" class="hint"></p></x-field>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border px-5 py-4 sm:px-6">
                        <button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button>
                        <button class="btn-primary">ບັນທຶກ</button>
                    </div>
                </form>
            </x-dialog>
        </x-slot:after>
    @endif
</x-layouts.app>
