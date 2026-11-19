<x-layouts.auth title="ຕັ້ງລະຫັດຜ່ານໃໝ່" heading="ຕັ້ງລະຫັດຜ່ານໃໝ່" subheading="ລະຫັດຜ່ານຢ່າງໜ້ອຍ 8 ຕົວອັກສອນ — ປະສົມຕົວໃຫຍ່, ຕົວນ້ອຍ ແລະ ຕົວເລກ ຈະປອດໄພກວ່າ.">
    <x-slot:top><span class="page-icon mb-6 !inline-flex">{{ icon('lock', 'size-6') }}</span></x-slot:top>
    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5" data-busy>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" label="ອີເມວ" required>
            <input id="f-email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="input @error('email') input-error @enderror">
        </x-field>
        <x-field name="password" label="ລະຫັດຜ່ານໃໝ່" required>
            <input id="f-password" name="password" type="password" required minlength="8" autocomplete="new-password" class="input @error('password') input-error @enderror" data-strength="pw-meter">
            @include('partials.password-meter', ['id' => 'pw-meter'])
        </x-field>
        <x-field name="password_confirmation" label="ຢືນຢັນລະຫັດຜ່ານໃໝ່" required>
            <input id="f-password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
        </x-field>
        <button class="btn-primary w-full" data-busy-label="ກຳລັງບັນທຶກ...">{{ icon('check', 'size-4') }} ບັນທຶກລະຫັດຜ່ານ</button>
    </form>
</x-layouts.auth>
