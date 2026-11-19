<x-layouts.public title="ຕັ້ງລະຫັດຜ່ານໃໝ່">
<div class="flex justify-center px-4 py-16">
    <div class="card w-full max-w-md p-8">
        <h1 class="text-[1.5rem]">ຕັ້ງລະຫັດຜ່ານໃໝ່</h1>
        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5" data-busy>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-field name="email" label="ອີເມວ">
                <input id="f-email" name="email" type="email" value="{{ old('email', $email) }}" required class="input @error('email') input-error @enderror">
            </x-field>
            <x-field name="password" label="ລະຫັດຜ່ານໃໝ່">
                <input id="f-password" name="password" type="password" required minlength="8" autocomplete="new-password" class="input @error('password') input-error @enderror" data-strength="pw-meter">
                @include('partials.password-meter', ['id' => 'pw-meter'])
            </x-field>
            <x-field name="password_confirmation" label="ຢືນຢັນລະຫັດຜ່ານໃໝ່">
                <input id="f-password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
            </x-field>
            <button class="btn-primary w-full" data-busy-label="ກຳລັງບັນທຶກ...">ບັນທຶກລະຫັດຜ່ານ</button>
        </form>
    </div>
</div>
</x-layouts.public>
