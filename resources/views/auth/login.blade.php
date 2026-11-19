{{-- Login / Register (tabs) — artboard "Login" --}}
<x-layouts.auth :title="$tab === 'register' ? 'ລົງທະບຽນ' : 'ເຂົ້າສູ່ລະບົບ'"
                :heading="$tab === 'register' ? 'ສ້າງບັນຊີໃໝ່' : 'ຍິນດີຕ້ອນຮັບກັບມາ'"
                :subheading="$tab === 'register' ? 'ບັນຊີໃໝ່ຈະລໍຖ້າຜູ້ດູແລລະບົບອະນຸມັດ (ປົກກະຕິ 1–2 ວັນ)' : 'ເຂົ້າສູ່ລະບົບເພື່ອເບິ່ງຂໍ້ມູນຄອບຄົວ ແລະ ກອງທຶນ'">
    <x-slot:top>
        <div class="segmented mb-8 grid w-full grid-cols-2" role="tablist">
            <a href="{{ route('login') }}" class="segment gap-1.5" role="tab" @if ($tab === 'login') aria-current="page" aria-selected="true" @endif>{{ icon('key-round', 'size-4') }} ເຂົ້າສູ່ລະບົບ</a>
            <a href="{{ route('register') }}" class="segment gap-1.5" role="tab" @if ($tab === 'register') aria-current="page" aria-selected="true" @endif>{{ icon('user-round-plus', 'size-4') }} ລົງທະບຽນ</a>
        </div>
    </x-slot:top>

    @if (\App\Http\Controllers\Auth\GoogleController::enabled())
        <a href="{{ route('google.redirect') }}" class="btn-outline mt-6 w-full">
            <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.6 12.2c0-.8-.1-1.5-.2-2.2H12v4.2h5.9a5 5 0 0 1-2.2 3.3v2.7h3.6c2.1-1.9 3.3-4.8 3.3-8z"/><path fill="#34A853" d="M12 23c3 0 5.5-1 7.3-2.7l-3.6-2.8c-1 .7-2.2 1.1-3.7 1.1-2.9 0-5.3-1.9-6.2-4.5H2.1v2.9A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.8 14.1a6.6 6.6 0 0 1 0-4.2V7H2.1a11 11 0 0 0 0 10l3.7-2.9z"/><path fill="#EA4335" d="M12 5.4c1.6 0 3.1.6 4.2 1.6l3.2-3.2A11 11 0 0 0 2.1 7l3.7 2.9C6.7 7.3 9.1 5.4 12 5.4z"/></svg>
            ດຳເນີນການດ້ວຍ Google
        </a>
        <div class="my-6 flex items-center gap-3 text-caption text-muted"><span class="h-px flex-1 bg-border"></span>ຫຼື ໃຊ້ອີເມວ<span class="h-px flex-1 bg-border"></span></div>
    @else
        <div class="mt-6"></div>
    @endif

    @if ($tab === 'login')
        <form method="POST" action="{{ route('login.store') }}" class="space-y-5" data-busy>
            @csrf
            <input type="hidden" name="redirect" value="{{ $redirect }}">
            <x-field name="email" label="ອີເມວ">
                <div class="relative"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">{{ icon('mail', 'size-4') }}</span>
<input id="f-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input pl-9 @error('email') input-error @enderror" placeholder="name@example.com"></div>
            </x-field>
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="f-password" class="label mb-0">ລະຫັດຜ່ານ</label>
                    <a href="{{ route('password.request') }}" class="link text-sm">ລືມລະຫັດຜ່ານ?</a>
                </div>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">{{ icon('lock', 'size-4') }}</span>
            <input id="f-password" name="password" type="password" required autocomplete="current-password" class="input pr-12 pl-9">
                    <button type="button" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-muted" data-reveal="f-password" aria-label="ສະແດງລະຫັດຜ່ານ">{{ icon('eye', 'size-5') }}</button>
                </div>
            </div>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="size-4 accent-[var(--primary)]"> ຈື່ການເຂົ້າສູ່ລະບົບ</label>
            <button class="btn-primary w-full" data-busy-label="ກຳລັງເຂົ້າສູ່ລະບົບ...">ເຂົ້າສູ່ລະບົບ {{ icon('arrow-right', 'size-4') }}</button>
        </form>
    @else
        <form method="POST" action="{{ route('register.store') }}" class="space-y-5" data-busy>
            @csrf
            <x-field name="display_name" label="ຊື່ທີ່ສະແດງ" required>
                <input id="f-display_name" name="display_name" value="{{ old('display_name') }}" required maxlength="100" class="input @error('display_name') input-error @enderror">
            </x-field>
            <x-field name="email" label="ອີເມວ" required>
                <div class="relative"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">{{ icon('mail', 'size-4') }}</span>
<input id="f-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input pl-9 @error('email') input-error @enderror" placeholder="name@example.com"></div>
            </x-field>
            <x-field name="password" label="ລະຫັດຜ່ານ" required>
                <input id="f-password" name="password" type="password" required minlength="8" autocomplete="new-password" class="input @error('password') input-error @enderror" data-strength="pw-meter">
                @include('partials.password-meter', ['id' => 'pw-meter'])
            </x-field>
            <x-field name="password_confirmation" label="ຢືນຢັນລະຫັດຜ່ານ" required>
                <input id="f-password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
            </x-field>
            <button class="btn-primary w-full" data-busy-label="ກຳລັງລົງທະບຽນ...">ລົງທະບຽນ {{ icon('arrow-right', 'size-4') }}</button>
        </form>
    @endif
</x-layouts.auth>
