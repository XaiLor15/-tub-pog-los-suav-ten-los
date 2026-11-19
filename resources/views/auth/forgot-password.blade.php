<x-layouts.public title="ລືມລະຫັດຜ່ານ">
<div class="flex justify-center px-4 py-16">
    <div class="card w-full max-w-md p-8">
        <h1 class="text-[1.5rem]">ລືມລະຫັດຜ່ານ</h1>
        <p class="mt-1 text-muted">ໃສ່ອີເມວທີ່ລົງທະບຽນ — ພວກເຮົາຈະສົ່ງລິ້ງຕັ້ງລະຫັດຜ່ານໃໝ່ໃຫ້.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5" data-busy>
            @csrf
            <x-field name="email" label="ອີເມວ">
                <input id="f-email" name="email" type="email" value="{{ old('email') }}" required class="input @error('email') input-error @enderror" placeholder="name@example.com">
            </x-field>
            <button class="btn-primary w-full" data-busy-label="ກຳລັງສົ່ງ...">ສົ່ງລິ້ງຕັ້ງລະຫັດໃໝ່</button>
        </form>
        <a href="{{ route('login') }}" class="link mt-6 inline-block text-sm">← ກັບໄປໜ້າເຂົ້າສູ່ລະບົບ</a>
    </div>
</div>
</x-layouts.public>
