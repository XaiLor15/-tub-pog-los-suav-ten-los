{{-- Profile — artboard "Profile" --}}
@php $m = $user->member; @endphp
<x-layouts.app title="ໂປຣໄຟລ໌" :crumbs="['ໂປຣໄຟລ໌ຂອງຂ້ອຍ']">
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="card overflow-hidden">
            <div class="hero-band h-28 sm:h-32" aria-hidden="true"></div>
            <div class="flex flex-col gap-4 px-6 pb-6 sm:flex-row sm:items-end">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="relative -mt-14 w-fit shrink-0">
                    @csrf @method('PUT')
                    <input type="hidden" name="display_name" value="{{ $user->display_name }}">
                    <span class="hero-avatar inline-flex rounded-full"><x-avatar :name="$user->display_name" :url="$user->avatarUrl()" size="xl" class="!size-28 !bg-primary !text-3xl !text-primary-foreground" /></span>
                    <label class="absolute right-0 bottom-1 inline-flex size-9 cursor-pointer items-center justify-center rounded-full border border-border bg-card shadow-lift transition hover:scale-105 hover:bg-surface-2 focus-within:shadow-[0_0_0_3px_var(--ring)]" data-tip="ປ່ຽນຮູບໂປຣໄຟລ໌">
                        {{ icon('camera', 'size-4') }}<span class="sr-only">ປ່ຽນຮູບໂປຣໄຟລ໌</span>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" data-autosubmit>
                    </label>
                </form>
                <div class="min-w-0 flex-1">
                    <h1 class="text-[1.625rem] leading-tight">{{ $user->display_name }}</h1>
                    <p class="mt-1 flex flex-wrap items-center gap-x-2 text-muted">{{ icon('mail', 'size-4') }} {{ $user->email }} <span aria-hidden="true">·</span> ເຂົ້າສູ່ລະບົບດ້ວຍ {{ $user->auth_provider === 'google' ? 'Google' : 'ອີເມວ' }}</p>
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        <x-badge :tone="$user->isPending() ? 'warning' : 'success'" dot>{{ $user->roleLabel() }}</x-badge>
                        @if ($user->approved_at)<x-badge>{{ icon('calendar-check', 'size-3.5') }} ສະມາຊິກແຕ່ {{ $user->approved_at->format('m/Y') }}</x-badge>@endif
                    </div>
                    @error('avatar')<p class="error-text">{{ $message }}</p>@enderror
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-danger-outline">{{ icon('log-out', 'size-4') }} ອອກຈາກລະບົບ</button></form>
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            <x-panel id="acc-h" title="ຂໍ້ມູນບັນຊີ" icon="user-round">
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4" data-busy>
                    @csrf @method('PUT')
                    <x-field name="display_name" label="ຊື່ທີ່ສະແດງ"><input id="f-display_name" name="display_name" value="{{ old('display_name', $user->display_name) }}" maxlength="100" class="input @error('display_name') input-error @enderror"></x-field>
                    <div>
                        <span class="label">ເຊື່ອມກັບຂໍ້ມູນສະມາຊິກ</span>
                        @if ($m)
                            <a href="{{ route('members.show', $m) }}" class="flex items-center gap-3 rounded-xl border border-border bg-surface-2/50 p-3 hover:bg-surface-2">
                                <x-avatar :member="$m" size="sm" />
                                <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $m->name }}</span><span class="text-sm text-muted">Phaj {{ $m->generation }}{{ $m->father ? ' · ລູກຂອງ '.$m->father->name : '' }}</span></span>
                                <span class="text-muted">{{ icon('chevron-right', 'size-4') }}</span>
                            </a>
                        @else
                            <p class="rounded-xl border border-dashed border-input p-3 text-sm text-muted">ຍັງບໍ່ໄດ້ເຊື່ອມ — ຜູ້ດູແລລະບົບຈະເຊື່ອມບັນຊີຂອງທ່ານກັບຂໍ້ມູນສະມາຊິກ</p>
                        @endif
                    </div>
                    <button class="btn-primary btn-sm">{{ icon('check', 'size-4') }} ບັນທຶກ</button>
                </form>
            </x-panel>

            <x-panel id="pw-h" :title="$user->password ? 'ປ່ຽນລະຫັດຜ່ານ' : 'ຕັ້ງລະຫັດຜ່ານ'" icon="key-round">
                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4" data-busy>
                    @csrf @method('PUT')
                    @if ($user->password)
                        <x-field name="current_password" label="ລະຫັດຜ່ານປັດຈຸບັນ"><input id="f-current_password" type="password" name="current_password" autocomplete="current-password" class="input @error('current_password') input-error @enderror"></x-field>
                    @endif
                    <x-field name="password" label="ລະຫັດຜ່ານໃໝ່">
                        <input id="f-password" type="password" name="password" minlength="8" autocomplete="new-password" class="input @error('password') input-error @enderror" data-strength="profile-pw">
                        @include('partials.password-meter', ['id' => 'profile-pw'])
                    </x-field>
                    <x-field name="password_confirmation" label="ຢືນຢັນລະຫັດຜ່ານໃໝ່"><input id="f-password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="input"></x-field>
                    <button class="btn-primary btn-sm">{{ icon('lock', 'size-4') }} {{ $user->password ? 'ປ່ຽນລະຫັດຜ່ານ' : 'ຕັ້ງລະຫັດຜ່ານ' }}</button>
                </form>
            </x-panel>
        </div>

        <x-panel id="look-h" title="ການສະແດງຜົນ" description="ບັນທຶກອັດຕະໂນມັດທັນທີທີ່ປ່ຽນ" icon="palette">
            <div class="flex flex-wrap gap-8">
                <fieldset>
                    <legend class="label flex items-center gap-1.5">{{ icon('sun', 'size-4 text-muted') }} ຮູບແບບສີ</legend>
                    <div class="segmented">
                        @foreach (['light' => 'ແຈ້ງ', 'dark' => 'ມືດ', 'system' => 'ຕາມອຸປະກອນ'] as $v => $l)
                            <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="pref_theme" value="{{ $v }}" class="sr-only" @checked($user->theme === $v)> {{ $l }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="label flex items-center gap-1.5">{{ icon('type', 'size-4 text-muted') }} ຂະໜາດຕົວອັກສອນ</legend>
                    <div class="segmented">
                        @foreach ([100 => 'ປົກກະຕິ', 112 => 'ໃຫຍ່ 112%', 125 => 'ໃຫຍ່ຫຼາຍ 125%'] as $v => $l)
                            <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="pref_font_scale" value="{{ $v }}" class="sr-only" @checked($user->font_scale === $v)> {{ $l }}</label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
            <div class="mt-5 rounded-xl border border-border bg-surface-2/60 p-4">
                <p class="text-xs font-semibold text-muted">ຕົວຢ່າງ</p>
                <p class="text-lg font-bold">ບຸນກິນຈຽງ ປີໃໝ່ມົ້ງ 2027</p>
                <p class="text-[0.9375rem]">ງານບຸນຈະຈັດຂຶ້ນແຕ່ວັນທີ 13 ຫາ 15/12/2026 ທີ່ສະໜາມບຸນ ໂພນສະຫວັນ.</p>
            </div>
        </x-panel>
    </div>
</x-layouts.app>
