<x-layouts.auth title="ລືມລະຫັດຜ່ານ" heading="ລືມລະຫັດຜ່ານ" subheading="ໃສ່ອີເມວທີ່ລົງທະບຽນ — ພວກເຮົາຈະສົ່ງລິ້ງຕັ້ງລະຫັດຜ່ານໃໝ່ໃຫ້.">
    <x-slot:top><span class="page-icon mb-6 !inline-flex">{{ icon('key-round', 'size-6') }}</span></x-slot:top>
    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5" data-busy>
        @csrf
        <x-field name="email" label="ອີເມວ" required>
            <div class="relative"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">{{ icon('mail', 'size-4') }}</span>
                <input id="f-email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="input pl-9 @error('email') input-error @enderror" placeholder="name@example.com"></div>
        </x-field>
        <button class="btn-primary w-full" data-busy-label="ກຳລັງສົ່ງ...">{{ icon('mail', 'size-4') }} ສົ່ງລິ້ງຕັ້ງລະຫັດໃໝ່</button>
    </form>
    <a href="{{ route('login') }}" class="group mt-8 inline-flex items-center gap-1.5 text-sm font-semibold text-primary">{{ icon('arrow-right', 'size-4 rotate-180 transition-transform group-hover:-translate-x-0.5') }} ກັບໄປໜ້າເຂົ້າສູ່ລະບົບ</a>
</x-layouts.auth>
