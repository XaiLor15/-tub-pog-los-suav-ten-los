{{-- Leadership — artboard "Leadership" (org chart + list/reorder) --}}
@php
    $exec = $groups->get('executive', collect());
    $heads = $groups->get('department_head', collect());
    $committee = $groups->get('committee', collect());
    $male = $leaders->filter(fn ($l) => $l->member?->sex === 'male')->count();
    $termText = $termStart ? $termStart->format('Y').'–'.$termEnd?->format('Y') : null;
    $phone = fn ($l) => $private ? phone_display($l->member?->phone) : null;
    $fill = fn ($l) => json_encode(['member_id' => $l->member_id, 'member_name' => $l->member?->name, 'position' => $l->position, 'group' => $l->group,
        'term_start' => $l->term_start?->toDateString(), 'term_end' => $l->term_end?->toDateString()]);
@endphp
<x-layouts.app title="ຄະນະນຳ" :crumbs="['ຕະກູນ', 'ຄະນະນຳ']">
    <x-page-header title="ຄະນະນຳຕະກູນ" icon="award" :description="($termText ? 'ວາລະ '.$termText.' · ' : '').'ຄັດເລືອກໂດຍກອງປະຊຸມໃຫຍ່ຂອງຕະກູນ'">
        <x-slot:actions>
            <button type="button" class="btn-outline" data-print>{{ icon('printer', 'size-4') }} ພິມ</button>
            @can('view-private')<a href="{{ route('leadership.export') }}" class="btn-outline">{{ icon('file-down', 'size-4') }} Export CSV</a>@endcan
            @if ($canManage)
                <button type="button" class="btn-primary" data-modal-open="leader-form" data-action="{{ route('leadership.store') }}" data-title="ເພີ່ມຕຳແໜ່ງ">{{ icon('plus', 'size-4') }} ເພີ່ມຕຳແໜ່ງ</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4" data-stagger>
        <x-stat-card label="ຄະນະນຳທັງໝົດ" icon="users" :value="$leaders->count()" :hint="'ຊາຍ '.$male.' · ຍິງ '.($leaders->count() - $male)" />
        <x-stat-card label="ຄະນະປະຈຳ" icon="crown" :value="$exec->count()" :hint="$exec->take(3)->pluck('position')->implode(' · ') ?: '—'" />
        <x-stat-card label="ຫົວໜ້າຝ່າຍ" icon="shield" :value="$heads->count()" :hint="'ກຳມະການ '.$committee->count().' ທ່ານ'" />
        <div class="card p-5">
            <span class="stat-icon size-10 rounded-xl">{{ icon('calendar-clock', 'size-5') }}</span>
            <p class="mt-3 text-sm font-medium text-muted">ໄລຍະວາລະ</p>
            <p class="mt-0.5 font-bold">{{ $termStart ? $termStart->format('m/Y').' – '.$termEnd?->format('m/Y') : 'ຍັງບໍ່ກຳນົດ' }}</p>
            @if ($progress !== null)
                <x-progress :value="$progress" class="mt-3" label="ໄລຍະວາລະທີ່ຜ່ານໄປ" tone="bg-primary" />
                <p class="mt-1 text-[0.8125rem] text-muted">{{ $remaining }}</p>
            @endif
        </div>
    </div>

    <div class="segmented no-print mt-6">
        <a href="{{ route('leadership.index') }}" class="segment gap-1.5" @if ($view === 'chart') aria-current="page" @endif>{{ icon('network', 'size-4') }} ແຜນຜັງການຈັດຕັ້ງ</a>
        <a href="{{ route('leadership.index', ['view' => 'list']) }}" class="segment gap-1.5" @if ($view === 'list') aria-current="page" @endif>{{ icon('list', 'size-4') }} ລາຍຊື່ ແລະ ຈັດລຳດັບ</a>
    </div>

    @if ($leaders->isEmpty())
        <div class="card mt-4"><x-empty icon="award" message="ຍັງບໍ່ມີຂໍ້ມູນຄະນະນຳ">@if ($canManage)<button type="button" class="btn-primary btn-sm" data-modal-open="leader-form" data-action="{{ route('leadership.store') }}">ເພີ່ມຕຳແໜ່ງທຳອິດ</button>@endif</x-empty></div>
    @elseif ($view === 'chart')
        <x-panel id="exec-h" title="ຄະນະປະຈຳ" icon="crown" class="mt-4">
            @if ($top = $exec->first())
                <div class="relative mx-auto max-w-[17rem] overflow-hidden rounded-2xl border border-heritage/50 bg-card text-center shadow-lift">
                    <div class="hero-band h-16" aria-hidden="true"></div>
                    <div class="-mt-10 px-5 pb-5">
                        <span class="hero-avatar inline-flex rounded-full ring-2 ring-heritage ring-offset-2 ring-offset-card"><x-avatar :member="$top->member" :url="$top->photoUrl()" size="xl" /></span>
                        <p class="mt-3 inline-flex items-center gap-1 rounded-full bg-heritage-soft px-2.5 py-0.5 text-xs font-semibold text-warning">{{ icon('crown', 'size-3.5') }} {{ $top->position }}</p>
                        <p class="mt-1.5 text-lg font-bold">{{ $top->member?->name }}</p>
                        <p class="num text-sm text-muted">{{ $phone($top) ?? '' }}</p>
                    </div>
                </div>
                @if ($exec->count() > 1)
                    <div class="mx-auto h-6 w-px bg-input" aria-hidden="true"></div>
                    <div class="grid gap-4 border-t border-input pt-6 sm:grid-cols-2 lg:grid-cols-3" data-stagger>
                        @foreach ($exec->skip(1) as $l)
                            <div class="rounded-xl border border-border bg-card p-5 text-center transition-shadow hover:shadow-lift">
                                <x-avatar :member="$l->member" :url="$l->photoUrl()" size="lg" class="mx-auto" />
                                <p class="mt-2 text-xs text-muted">{{ $l->position }}</p>
                                <p class="font-bold">{{ $l->member?->name }}</p>
                                <p class="num text-sm text-muted">{{ $phone($l) ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="text-sm text-muted">ຍັງບໍ່ມີ</p>
            @endif
        </x-panel>
        <x-panel id="heads-h" title="ຫົວໜ້າຝ່າຍ" icon="shield" class="mt-4">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" data-stagger>
                @forelse ($heads as $l)
                    <div class="flex items-center gap-3 rounded-xl border border-border p-3 transition-shadow hover:shadow-soft">
                        <x-avatar :member="$l->member" :url="$l->photoUrl()" size="md" />
                        <span class="min-w-0 leading-tight"><span class="block truncate text-xs text-muted">{{ $l->position }}</span><span class="block font-bold">{{ $l->member?->name }}</span><span class="num block text-xs text-muted">{{ $phone($l) ?? '' }}</span></span>
                    </div>
                @empty
                    <p class="text-sm text-muted">ຍັງບໍ່ມີ</p>
                @endforelse
            </div>
        </x-panel>
        <x-panel id="committee-h" title="ກຳມະການ" icon="users" class="mt-4">
            <x-slot:actions><span class="text-sm text-muted">{{ $committee->count() }} ທ່ານ</span></x-slot:actions>
            <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-5" data-stagger>
                @forelse ($committee as $l)
                    <div class="flex items-center gap-3 rounded-xl bg-surface-2/70 p-3"><x-avatar :member="$l->member" :url="$l->photoUrl()" size="sm" /><span class="truncate font-medium">{{ $l->member?->name }}</span></div>
                @empty
                    <p class="text-sm text-muted">ຍັງບໍ່ມີ</p>
                @endforelse
            </div>
        </x-panel>
    @else
        <div class="card mt-4 overflow-x-auto">
            <table class="data-table">
                <thead><tr><th class="w-12">#</th><th>ຊື່</th><th>ຕຳແໜ່ງ</th><th>ກຸ່ມ</th><th>ເບີໂທ</th><th>ວາລະ</th>@if ($canManage)<th class="no-print text-right">ຈັດການ</th>@endif</tr></thead>
                <tbody>
                @foreach ($leaders as $l)
                    <tr>
                        <td class="num text-muted">{{ $l->sort_order }}</td>
                        <td><span class="flex items-center gap-3"><x-avatar :member="$l->member" :url="$l->photoUrl()" size="sm" /><span class="font-semibold">{{ $l->member?->name }}</span></span></td>
                        <td>{{ $l->position }}</td>
                        <td><x-badge tone="primary">{{ $l->groupLabel() }}</x-badge></td>
                        <td class="num whitespace-nowrap">@if ($private){{ phone_display($l->member?->phone) }}@else<x-locked />@endif</td>
                        <td class="whitespace-nowrap text-sm">{{ $l->term_start ? $l->term_start->format('m/Y').' – '.$l->term_end?->format('m/Y') : '—' }}</td>
                        @if ($canManage)
                            <td class="no-print">
                                <div class="flex justify-end gap-1">
                                    @foreach (['up' => 'arrow-up', 'down' => 'arrow-down'] as $dir => $ic)
                                        <form method="POST" action="{{ route('leadership.reorder') }}">@csrf<input type="hidden" name="id" value="{{ $l->id }}"><input type="hidden" name="direction" value="{{ $dir }}">
                                            <button class="icon-btn-sm" @disabled(($dir === 'up' && $loop->parent->first) || ($dir === 'down' && $loop->parent->last)) aria-label="ຍ້າຍ{{ $dir === 'up' ? 'ຂຶ້ນ' : 'ລົງ' }}">{{ icon($ic, 'size-4') }}</button></form>
                                    @endforeach
                                    <button type="button" class="icon-btn-sm" data-modal-open="leader-form" data-action="{{ route('leadership.update', $l) }}" data-method="PUT" data-title="ແກ້ໄຂຕຳແໜ່ງ" data-fill="{{ $fill($l) }}" aria-label="ແກ້ໄຂ">{{ icon('pencil', 'size-4') }}</button>
                                    <form method="POST" action="{{ route('leadership.destroy', $l) }}" data-confirm="ລຶບ {{ $l->position }} — {{ $l->member?->name }}?" data-confirm-danger data-confirm-label="ລຶບ">@csrf @method('DELETE')
                                        <button class="icon-btn-sm text-destructive" aria-label="ລຶບ">{{ icon('trash-2', 'size-4') }}</button></form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($canManage)
        <x-slot:after>
            <x-dialog id="leader-form" title="ເພີ່ມຕຳແໜ່ງ" :open="$errors->any() && old('_form') === 'leader'">
                <form method="POST" action="{{ route('leadership.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col" data-fillable data-busy>
                    @csrf <input type="hidden" name="_method" value="POST" data-default="POST"><input type="hidden" name="_form" value="leader">
                    <div class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
                        <x-field name="member_id" label="ສະມາຊິກ" required>
                            <div data-member-picker>
                                <input type="hidden" name="member_id" value="{{ old('member_id') }}" data-picker-value>
                                <input name="member_name" value="{{ old('member_name') }}" class="input @error('member_id') input-error @enderror" placeholder="ພິມຊື່ເພື່ອຄົ້ນຫາ..." data-picker-input>
                            </div>
                        </x-field>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-field name="position" label="ຕຳແໜ່ງ" required><input id="f-position" name="position" value="{{ old('position') }}" maxlength="100" class="input" placeholder="ເຊັ່ນ ປະທານຕະກູນ"></x-field>
                            <x-field name="group" label="ກຸ່ມ" required><x-select name="group" :options="\App\Support\Lookups::LEADERSHIP_GROUPS" :selected="old('group', 'committee')" /></x-field>
                            <x-field name="term_start" label="ເລີ່ມວາລະ"><input id="f-term_start" type="date" name="term_start" value="{{ old('term_start') }}" class="input"></x-field>
                            <x-field name="term_end" label="ຈົບວາລະ"><input id="f-term_end" type="date" name="term_end" value="{{ old('term_end') }}" class="input"></x-field>
                        </div>
                        <x-field name="photo" label="ຮູບ (ທາງເລືອກ — ຖ້າບໍ່ໃສ່ ໃຊ້ຮູບສະມາຊິກ)">
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="input py-2" data-preview="leader-photo" data-max-mb="10">
                            <p id="leader-photo" class="hint"></p>
                        </x-field>
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
