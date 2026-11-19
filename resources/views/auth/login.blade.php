{{-- Login / Register (tabs) — artboard "Login" --}}
<x-layouts.public :title="$tab === 'register' ? 'ລົງທະບຽນ' : 'ເຂົ້າສູ່ລະບົບ'" bare>
<div class="grid min-h-dvh lg:grid-cols-[48%_1fr]">
    <section class="surface-brand relative hidden overflow-hidden px-14 py-14 text-white lg:flex lg:flex-col">
        <div class="lattice pointer-events-none absolute right-0 bottom-0 h-2/3 w-1/2 opacity-60"></div>
        <a href="{{ route('dashboard') }}" class="relative flex items-center gap-3">
            <x-logo class="size-12" />
            <span class="leading-tight"><span class="block text-lg font-bold">Kwv Tij Hmoob Xyooj</span><span class="block text-sm text-white/70">ລະບົບຄຸ້ມຄອງຕະກູນ</span></span>
        </a>
        <div class="relative my-auto max-w-lg">
            <p class="text-[2.25rem] leading-snug font-bold">ເຊື່ອມໂຍງພີ່ນ້ອງ<br>ຮັກສາຮາກເຫງົ້າຂອງຕະກູນ</p>
            <p class="mt-4 text-white/80">ແຜນຜັງຄອບຄົວ, ກິດຈະກຳ, ຂ່າວສານ ແລະ ກອງທຶນ ຢູ່ໃນບ່ອນດຽວ — ໂປ່ງໃສ ແລະ ປອດໄພ.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="relative font-semibold text-white underline underline-offset-4">ເບິ່ງຂໍ້ມູນສາທາລະນະ ໂດຍບໍ່ຕ້ອງເຂົ້າສູ່ລະບົບ →</a>
    </section>

    <section class="flex items-center justify-center px-5 py-10 sm:px-10">
        <div class="w-full max-w-[27.5rem]">
            <a href="{{ route('dashboard') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                <x-logo class="size-10" /><span class="font-bold">Kwv Tij Hmoob Xyooj</span>
            </a>
            <div class="segmented grid w-full grid-cols-2" role="tablist">
                <a href="{{ route('login') }}" class="segment" role="tab" @if ($tab === 'login') aria-current="page" @endif>ເຂົ້າສູ່ລະບົບ</a>
                <a href="{{ route('register') }}" class="segment" role="tab" @if ($tab === 'register') aria-current="page" @endif>ລົງທະບຽນ</a>
            </div>

            @if ($tab === 'login')
                <h1 class="mt-8 text-[1.625rem]">ຍິນດີຕ້ອນຮັບກັບມາ</h1>
                <p class="text-muted">ເຂົ້າສູ່ລະບົບເພື່ອເບິ່ງຂໍ້ມູນຄອບຄົວ ແລະ ກອງທຶນ</p>
            @else
                <h1 class="mt-8 text-[1.625rem]">ສ້າງບັນຊີໃໝ່</h1>
                <p class="text-muted">ບັນຊີໃໝ່ຈະລໍຖ້າຜູ້ດູແລລະບົບອະນຸມັດ (ປົກກະຕິ 1–2 ວັນ)</p>
            @endif

            @if (\App\Http\Controllers\Auth\GoogleController::enabled())
                <a href="{{ route('google.redirect') }}" class="btn-outline mt-6 w-full">
                    <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M22.6 12.2c0-.8-.1-1.5-.2-2.2H12v4.2h5.9a5 5 0 0 1-2.2 3.3v2.7h3.6c2.1-1.9 3.3-4.8 3.3-8z"/><path fill="#34A853" d="M12 23c3 0 5.5-1 7.3-2.7l-3.6-2.8c-1 .7-2.2 1.1-3.7 1.1-2.9 0-5.3-1.9-6.2-4.5H2.1v2.9A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.8 14.1a6.6 6.6 0 0 1 0-4.2V7H2.1a11 11 0 0 0 0 10l3.7-2.9z"/><path fill="#EA4335" d="M12 5.4c1.6 0 3.1.6 4.2 1.6l3.2-3.2A11 11 0 0 0 2.1 7l3.7 2.9C6.7 7.3 9.1 5.4 12 5.4z"/></svg>
                    ດຳເນີນການດ້ວຍ Google
                </a>
                <div class="my-6 flex items-center gap-3 text-sm text-muted"><span class="h-px flex-1 bg-border"></span>ຫຼື ໃຊ້ອີເມວ<span class="h-px flex-1 bg-border"></span></div>
            @else
                <div class="mt-6"></div>
            @endif

            @if ($tab === 'login')
                <form method="POST" action="{{ route('login.store') }}" class="space-y-5" data-busy>
                    @csrf
                    <input type="hidden" name="redirect" value="{{ $redirect }}">
                    <x-field name="email" label="ອີເມວ">
                        <input id="f-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input @error('email') input-error @enderror" placeholder="name@example.com">
                    </x-field>
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="f-password" class="label mb-0">ລະຫັດຜ່ານ</label>
                            <a href="{{ route('password.request') }}" class="link text-sm">ລືມລະຫັດຜ່ານ?</a>
                        </div>
                        <div class="relative">
                            <input id="f-password" name="password" type="password" required autocomplete="current-password" class="input pr-12">
                            <button type="button" class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-muted" data-reveal="f-password" aria-label="ສະແດງລະຫັດຜ່ານ">{{ icon('eye', 'size-5') }}</button>
                        </div>
                    </div>
                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="size-4 accent-[var(--primary)]"> ຈື່ການເຂົ້າສູ່ລະບົບ</label>
                    <button class="btn-primary w-full" data-busy-label="ກຳລັງເຂົ້າສູ່ລະບົບ...">ເຂົ້າສູ່ລະບົບ</button>
                </form>
            @else
                <form method="POST" action="{{ route('register.store') }}" class="space-y-5" data-busy>
                    @csrf
                    <x-field name="display_name" label="ຊື່ທີ່ສະແດງ" required>
                        <input id="f-display_name" name="display_name" value="{{ old('display_name') }}" required maxlength="100" class="input @error('display_name') input-error @enderror">
                    </x-field>
                    <x-field name="email" label="ອີເມວ" required>
                        <input id="f-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input @error('email') input-error @enderror" placeholder="name@example.com">
                    </x-field>
                    <x-field name="password" label="ລະຫັດຜ່ານ" required>
                        <input id="f-password" name="password" type="password" required minlength="8" autocomplete="new-password" class="input @error('password') input-error @enderror" data-strength="pw-meter">
                        @include('partials.password-meter', ['id' => 'pw-meter'])
                    </x-field>
                    <x-field name="password_confirmation" label="ຢືນຢັນລະຫັດຜ່ານ" required>
                        <input id="f-password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
                    </x-field>
                    <button class="btn-primary w-full" data-busy-label="ກຳລັງລົງທະບຽນ...">ລົງທະບຽນ</button>
                </form>
            @endif
        </div>
    </section>
</div>
</x-layouts.public>
