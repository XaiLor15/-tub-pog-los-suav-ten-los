{{-- Profile — artboard "Profile" --}}
@php $m = $user->member; @endphp
<x-layouts.app title="ໂປຣໄຟລ໌" :crumbs="['ໂປຣໄຟລ໌ຂອງຂ້ອຍ']">
    <div class="mx-auto max-w-4xl space-y-6">
        <section class="card flex flex-col gap-5 p-6 sm:flex-row sm:items-center">
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="relative w-fit">
                @csrf @method('PUT')
                <input type="hidden" name="display_name" value="{{ $user->display_name }}">
                <x-avatar :name="$user->display_name" :url="$user->avatarUrl()" size="xl" class="!size-24 !bg-primary !text-3xl !text-primary-foreground" />
                <label class="absolute -right-1 -bottom-1 inline-flex size-9 cursor-pointer items-center justify-center rounded-full border border-border bg-card shadow-soft hover:bg-surface-2" title="ປ່ຽນຮູບ">
                    {{ icon('camera', 'size-4') }}<span class="sr-only">ປ່ຽນຮູບໂປຣໄຟລ໌</span>
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" data-autosubmit>
                </label>
            </form>
            <div class="min-w-0 flex-1">
                <h1 class="text-[1.625rem]">{{ $user->display_name }}</h1>
                <p class="text-muted">{{ $user->email }} · ເຂົ້າສູ່ລະບົບດ້ວຍ {{ $user->auth_provider === 'google' ? 'Google' : 'ອີເມວ' }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <x-badge :tone="$user->isPending() ? 'warning' : 'success'">{{ $user->roleLabel() }}</x-badge>
                    @if ($user->approved_at)<x-badge>ສະມາຊິກແຕ່ {{ $user->approved_at->format('m/Y') }}</x-badge>@endif
                </div>
                @error('avatar')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-danger-outline">{{ icon('log-out', 'size-4') }} ອອກຈາກລະບົບ</button></form>
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            <section class="card card-pad" aria-labelledby="acc-h">
                <h2 id="acc-h" class="text-[1.0625rem] font-semibold">ຂໍ້ມູນບັນຊີ</h2>
                <form method="POST" action="{{ route('profile.update') }}" class="mt-4 space-y-4" data-busy>
                    @csrf @method('PUT')
                    <x-field name="display_name" label="ຊື່ທີ່ສະແດງ"><input id="f-display_name" name="display_name" value="{{ old('display_name', $user->display_name) }}" maxlength="100" class="input @error('display_name') input-error @enderror"></x-field>
                    <div>
                        <span class="label">ເຊື່ອມກັບຂໍ້ມູນສະມາຊິກ</span>
                        @if ($m)
                            <a href="{{ route('members.show', $m) }}" class="flex items-center gap-3 rounded-xl border border-border bg-surface-2/50 p-3 hover:bg-surface-2">
                                <x-avatar :member="$m" size="sm" />
                                <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $m->name }}</span><span class="text-sm text-muted">Phaj {{ $m->generation }}{{ $m->father ? ' · ລູກຂອງ '.$m->father->name : '' }}</span></span>
                                <span class="text-sm font-semibold">ເບິ່ງ →</span>
                            </a>
                        @else
                            <p class="rounded-xl border border-dashed border-input p-3 text-sm text-muted">ຍັງບໍ່ໄດ້ເຊື່ອມ — ຜູ້ດູແລລະບົບຈະເຊື່ອມບັນຊີຂອງທ່ານກັບຂໍ້ມູນສະມາຊິກ</p>
                        @endif
                    </div>
                    <button class="btn-primary btn-sm">ບັນທຶກ</button>
                </form>
            </section>

            <section class="card card-pad" aria-labelledby="pw-h">
                <h2 id="pw-h" class="text-[1.0625rem] font-semibold">{{ $user->password ? 'ປ່ຽນລະຫັດຜ່ານ' : 'ຕັ້ງລະຫັດຜ່ານ' }}</h2>
                <form method="POST" action="{{ route('profile.password') }}" class="mt-4 space-y-4" data-busy>
                    @csrf @method('PUT')
                    @if ($user->password)
                        <x-field name="current_password" label="ລະຫັດຜ່ານປັດຈຸບັນ"><input id="f-current_password" type="password" name="current_password" autocomplete="current-password" class="input @error('current_password') input-error @enderror"></x-field>
                    @endif
                    <x-field name="password" label="ລະຫັດຜ່ານໃໝ່">
                        <input id="f-password" type="password" name="password" minlength="8" autocomplete="new-password" class="input @error('password') input-error @enderror" data-strength="profile-pw">
                        @include('partials.password-meter', ['id' => 'profile-pw'])
                    </x-field>
                    <x-field name="password_confirmation" label="ຢືນຢັນລະຫັດຜ່ານໃໝ່"><input id="f-password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="input"></x-field>
                    <button class="btn-primary btn-sm">{{ $user->password ? 'ປ່ຽນລະຫັດຜ່ານ' : 'ຕັ້ງລະຫັດຜ່ານ' }}</button>
                </form>
            </section>
        </div>

        <section class="card card-pad" aria-labelledby="look-h">
            <h2 id="look-h" class="text-[1.0625rem] font-semibold">ການສະແດງຜົນ</h2>
            <div class="mt-4 flex flex-wrap gap-8">
                <fieldset>
                    <legend class="label">ຮູບແບບສີ</legend>
                    <div class="segmented">
                        @foreach (['light' => 'ແຈ້ງ', 'dark' => 'ມືດ', 'system' => 'ຕາມອຸປະກອນ'] as $v => $l)
                            <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="pref_theme" value="{{ $v }}" class="sr-only" @checked($user->theme === $v)> {{ $l }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="label">ຂະໜາດຕົວອັກສອນ</legend>
                    <div class="segmented">
                        @foreach ([100 => 'ປົກກະຕິ', 112 => 'ໃຫຍ່ 112%', 125 => 'ໃຫຍ່ຫຼາຍ 125%'] as $v => $l)
                            <label class="segment has-checked:bg-card has-checked:text-foreground has-checked:shadow-soft"><input type="radio" name="pref_font_scale" value="{{ $v }}" class="sr-only" @checked($user->font_scale === $v)> {{ $l }}</label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
            <div class="mt-5 rounded-xl bg-surface-2/70 p-4">
                <p class="text-xs text-muted">ຕົວຢ່າງ</p>
                <p class="text-lg font-bold">ບຸນກິນຈຽງ ປີໃໝ່ມົ້ງ 2027</p>
                <p class="text-[0.9375rem]">ງານບຸນຈະຈັດຂຶ້ນແຕ່ວັນທີ 13 ຫາ 15/12/2026 ທີ່ສະໜາມບຸນ ໂພນສະຫວັນ.</p>
            </div>
            <p class="mt-2 text-[0.8125rem] text-muted">ບັນທຶກອັດຕະໂນມັດ</p>
        </section>
    </div>
</x-layouts.app>
