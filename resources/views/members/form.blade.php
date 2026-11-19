{{-- MemberForm dialog — artboard "MemberForm" (add / edit). Behaviour in resources/js/member-form.js. --}}
@php
    $e = $editing;
    $presetFather = $e?->father ?? (request('father') ? \App\Models\Member::find(request('father')) : null);
    $fatherId = old('father_id', $e?->father_id ?? $presetFather?->id);
    $father = $fatherId ? \App\Models\Member::find($fatherId) : null;
    $alive = (string) old('is_alive', $e ? (int) $e->is_alive : 1);
    $yearOnly = (bool) old('birth_year_only', $e?->birth_year_only ?? false);
    $dups = session('member_duplicates', []);
@endphp
<x-dialog id="member-form" :title="$e ? 'ແກ້ໄຂສະມາຊິກ' : 'ເພີ່ມສະມາຊິກໃໝ່'" subtitle="ຊ່ອງທີ່ມີ * ຕ້ອງໃສ່" size="lg" :open="$open">
    <form method="POST" action="{{ $e ? route('members.update', $e) : route('members.store') }}" enctype="multipart/form-data"
          class="flex min-h-0 flex-1 flex-col" data-member-form data-member-id="{{ $e?->id }}" data-busy novalidate>
        @csrf
        @if ($e) @method('PUT') @endif
        <input type="hidden" name="_form" value="member">
        <input type="hidden" name="insert_at" value="{{ old('insert_at', $e?->birth_order) }}" data-insert-at>

        <div class="flex-1 space-y-7 overflow-y-auto px-5 py-5 sm:px-6">
            {{-- Duplicate warning (find_duplicate_members) --}}
            <div data-dup-box @class(['rounded-xl border border-warning/30 bg-warning-bg p-4', 'hidden' => ! $dups && ! $errors->has('duplicate_confirmed')])>
                <p class="flex items-center gap-2 font-semibold text-warning">{{ icon('triangle-alert', 'size-5') }} ອາດມີຂໍ້ມູນຊ້ຳ <span class="font-normal">— ມີສະມາຊິກທີ່ມີຊື່ ແລະ ວັນເກີດ ກົງກັນ:</span></p>
                <div data-dup-list class="mt-3 space-y-2">
                    @foreach ($dups as $d)
                        <div class="flex items-center gap-3 rounded-lg border border-border bg-card px-3 py-2">
                            <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $d['name'] }}</span><span class="block text-xs text-muted">{{ $d['sub'] }}</span></span>
                            <a href="{{ $d['url'] }}" target="_blank" class="link text-sm">ເປີດເບິ່ງ</a>
                        </div>
                    @endforeach
                </div>
                <label class="mt-3 flex min-h-11 items-center gap-2 text-sm text-foreground">
                    <input type="checkbox" name="duplicate_confirmed" value="1" class="size-4" @checked(old('duplicate_confirmed'))> ຂ້ອຍກວດແລ້ວ — ບໍ່ແມ່ນຄົນດຽວກັນ, ບັນທຶກຕໍ່
                </label>
                @error('duplicate_confirmed')<p class="error-text">{{ $message }}</p>@enderror
            </div>

            {{-- 1. Basics --}}
            <fieldset>
                <legend class="mb-3 flex items-center gap-2 font-semibold"><span class="inline-flex size-6 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">1</span> ຂໍ້ມູນພື້ນຖານ</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="name" label="ຊື່" required>
                        <input id="f-name" name="name" value="{{ old('name', $e?->name) }}" maxlength="100" required class="input @error('name') input-error @enderror" data-dup-name autocomplete="off">
                    </x-field>
                    <x-field name="hmong_name" label="ຊື່ມົ້ງ">
                        <input id="f-hmong_name" name="hmong_name" value="{{ old('hmong_name', $e?->hmong_name) }}" maxlength="100" class="input" placeholder="ເຊັ່ນ Txawj Xyooj">
                    </x-field>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-[auto_1fr_auto]">
                    <div>
                        <span class="label">ເພດ <span class="text-destructive">*</span></span>
                        <div class="flex gap-2" role="radiogroup">
                            @foreach (['male' => 'ຊາຍ', 'female' => 'ຍິງ'] as $v => $l)
                                <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-input px-4 has-checked:border-primary has-checked:ring-2 has-checked:ring-primary/20">
                                    <input type="radio" name="sex" value="{{ $v }}" @checked(old('sex', $e?->sex ?? 'male') === $v) class="accent-[var(--primary)]"> {{ $l }}
                                </label>
                            @endforeach
                        </div>
                        @error('sex')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="f-birth_date" class="label">ວັນເກີດ</label>
                        <input id="f-birth_date" type="date" name="birth_date" max="{{ now()->toDateString() }}" value="{{ old('birth_date', $e && ! $e->birth_year_only ? $e->birth_date?->toDateString() : '') }}" class="input @error('birth_date') input-error @enderror" data-dup-birth @if ($yearOnly) hidden @endif>
                        <input type="number" name="birth_year" min="1700" max="{{ now()->year }}" placeholder="ປີເກີດ ເຊັ່ນ 1952" value="{{ old('birth_year', $e && $e->birth_year_only ? $e->birth_date?->year : '') }}" class="input" data-birth-year @unless ($yearOnly) hidden @endunless>
                        <label class="mt-1 flex items-center gap-2 text-sm"><input type="checkbox" name="birth_year_only" value="1" @checked($yearOnly) data-year-only class="size-4"> ຮູ້ແຕ່ປີເກີດ</label>
                        @error('birth_date')<p class="error-text">{{ $message }}</p>@enderror
                        @error('birth_year')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <span class="label">ສະຖານະ</span>
                        <div class="segmented">
                            <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="is_alive" value="1" class="sr-only" @checked($alive === '1') data-alive> ມີຊີວິດ</label>
                            <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="is_alive" value="0" class="sr-only" @checked($alive === '0') data-alive> ເສຍຊີວິດແລ້ວ</label>
                        </div>
                        <div data-death-year @if ($alive === '1') hidden @endif class="mt-2">
                            <input type="number" name="death_year" min="1700" max="{{ now()->year }}" placeholder="ປີເສຍຊີວິດ" value="{{ old('death_year', $e?->death_year) }}" class="input" aria-label="ປີເສຍຊີວິດ">
                            @error('death_year')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </fieldset>

            {{-- 2. Paternal line & siblings --}}
            <fieldset>
                <legend class="mb-1 flex items-center gap-2 font-semibold"><span class="inline-flex size-6 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">2</span> ສາຍພໍ່ ແລະ ອ້າຍນ້ອງ</legend>
                <p class="mb-3 text-sm text-muted">ລະບົບບັນທຶກສາຍຕະກູນທາງຝ່າຍພໍ່. ອ້າຍນ້ອງ ຄື ລູກທຸກຄົນຂອງພໍ່ຄົນດຽວກັນ ແລະ ຈະເຊື່ອມໂຍງໃຫ້ອັດຕະໂນມັດ.</p>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="f-father-search" class="label">ພໍ່</label>
                        <input type="hidden" name="father_id" value="{{ $fatherId }}" data-father-id>
                        <div class="relative" data-combobox>
                            <input id="f-father-search" class="input pr-9 @error('father_id') input-error @enderror" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="father-options"
                                   placeholder="ຄົ້ນຫາຊື່ພໍ່ (ເພດຊາຍ)..." value="{{ $father ? $father->name.' · Phaj '.$father->generation : '' }}" data-father-search>
                            <button type="button" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted" data-father-clear aria-label="ລ້າງ (ຕົ້ນຕະກູນ)">{{ icon('x', 'size-4') }}</button>
                            <ul id="father-options" role="listbox" class="absolute z-20 mt-1 hidden max-h-64 w-full overflow-y-auto rounded-xl border border-border bg-card p-1 shadow-pop" data-father-options></ul>
                        </div>
                        @error('father_id')<p class="error-text">{{ $message }}</p>@enderror
                        <div class="mt-3 flex items-center gap-3 rounded-xl border border-success/30 bg-success-bg p-3" data-phaj-auto @unless ($father) hidden @endunless>
                            <span class="inline-flex size-10 items-center justify-center rounded-lg border border-success/30 bg-card text-lg font-bold text-success" data-phaj-value>{{ $father ? $father->generation + 1 : '' }}</span>
                            <span class="text-sm"><span class="block font-semibold text-success">Phaj <span data-phaj-value>{{ $father ? $father->generation + 1 : '' }}</span> (ຄຳນວນອັດຕະໂນມັດ)</span><span class="text-muted">Phaj ຂອງພໍ່ (<span data-father-phaj>{{ $father?->generation }}</span>) + 1</span></span>
                        </div>
                        <div class="mt-3" data-phaj-manual @if ($father) hidden @endif>
                            <x-field name="generation" label="Phaj (ຕົ້ນຕະກູນ — ບໍ່ມີພໍ່)" hint="ໃສ່ເອງ ≥ 1">
                                <input id="f-generation" type="number" name="generation" min="1" max="99" value="{{ old('generation', $e?->generation ?? 1) }}" class="input w-32">
                            </x-field>
                        </div>
                    </div>
                    <div>
                        <span class="label">ລຳດັບການເກີດໃນຄອບຄົວ</span>
                        <div data-order-box class="rounded-xl border border-border">
                            <p class="p-4 text-sm text-muted" data-order-empty>ເລືອກພໍ່ກ່ອນ ເພື່ອຈັດລຳດັບການເກີດ</p>
                            <ol data-order-list class="divide-y divide-border"></ol>
                        </div>
                        <p class="hint-ok" data-order-summary></p>
                    </div>
                </div>
            </fieldset>

            {{-- 3. Contact & address --}}
            <fieldset>
                <legend class="mb-3 flex items-center gap-2 font-semibold"><span class="inline-flex size-6 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">3</span> ຕິດຕໍ່ ແລະ ທີ່ຢູ່ <x-badge tone="neutral" class="text-xs">{{ icon('lock', 'size-3') }} ບໍ່ສະແດງຕໍ່ສາທາລະນະ</x-badge></legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field name="phone" label="ເບີໂທ">
                        <input id="f-phone" name="phone" inputmode="tel" value="{{ old('phone', $e?->phone ? phone_display($e->phone) : '') }}" class="input @error('phone') input-error @enderror" placeholder="020 5512 3344" data-phone>
                        <p class="hint-ok" data-phone-preview></p>
                    </x-field>
                    <x-field name="province" label="ແຂວງ">
                        <x-select name="province" :options="array_combine(\App\Support\Lookups::PROVINCES, \App\Support\Lookups::PROVINCES)" :selected="old('province', $e?->province)" placeholder="— ເລືອກແຂວງ —" />
                    </x-field>
                    <x-field name="village" label="ບ້ານ / ເມືອງ">
                        <input id="f-village" name="village" value="{{ old('village', $e?->village) }}" maxlength="100" class="input" placeholder="ບ້ານ...">
                    </x-field>
                </div>
            </fieldset>

            {{-- 4. Photo --}}
            <fieldset>
                <legend class="mb-3 flex items-center gap-2 font-semibold"><span class="inline-flex size-6 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">4</span> ຮູບພາບ</legend>
                <label class="flex cursor-pointer items-center gap-4 rounded-xl border-2 border-dashed border-input p-5 hover:bg-surface-2/50">
                    <span class="inline-flex size-12 items-center justify-center rounded-full bg-surface-2 text-muted">{{ icon('upload', 'size-5') }}</span>
                    <span><span class="block font-semibold">ເລືອກຮູບ <span class="font-normal text-muted">ຫຼື ລາກມາວາງ</span></span>
                    <span class="block text-[0.8125rem] text-muted">JPG, PNG, WebP · ບໍ່ເກີນ 10 MB · ຍໍ່ເປັນ 1920px ອັດຕະໂນມັດ</span></span>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only" data-preview="photo-preview" data-max-mb="10">
                </label>
                <p id="photo-preview" class="hint"></p>
                @error('photo')<p class="error-text">{{ $message }}</p>@enderror
            </fieldset>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-border bg-surface-2/40 px-5 py-4 sm:px-6">
            <p class="hidden text-[0.8125rem] text-warning sm:block" data-dup-footer @unless ($dups) hidden @endunless>ກະລຸນາກວດຂໍ້ມູນຊ້ຳກ່ອນບັນທຶກ</p>
            <span class="flex-1"></span>
            <button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button>
            <button class="btn-primary" data-busy-label="ກຳລັງບັນທຶກ...">ບັນທຶກສະມາຊິກ</button>
        </div>
    </form>
</x-dialog>
