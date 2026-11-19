{{--
    AuthLayout: heritage brand panel (desktop) + centred form column. Used by login/register, forgot and reset password.
    <x-layouts.auth title="…" heading="…" subheading="…">form</x-layouts.auth>
--}}
@props(['title', 'heading', 'subheading' => null])
@php
    $features = [
        ['network', 'ແຜນຜັງຕະກູນ', 'ສາຍສືບທອດທາງຝ່າຍພໍ່ ທຸກ Phaj'],
        ['calendar-days', 'ກິດຈະກຳ ແລະ ຂ່າວສານ', 'ນັດໝາຍ, ບຸນ ແລະ ປະກາດຂອງຕະກູນ'],
        ['wallet', 'ການເງິນໂປ່ງໃສ', 'ລາຍຮັບ-ລາຍຈ່າຍ ແລະ ກອງທຶນ'],
        ['shield-check', 'ຂໍ້ມູນປອດໄພ', 'ຂໍ້ມູນສ່ວນຕົວເຫັນສະເພາະສະມາຊິກ'],
    ];
@endphp
<x-layouts.public :title="$title" bare>
<div class="grid min-h-dvh lg:grid-cols-[minmax(0,46%)_1fr]">
    <section class="surface-brand relative hidden overflow-hidden px-12 py-12 text-white lg:flex lg:flex-col xl:px-16">
        <div class="lattice pointer-events-none absolute inset-0 opacity-50" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-24 -bottom-24 size-[28rem] rotate-45 rounded-[4rem] border border-heritage/25" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-8 -bottom-8 size-[20rem] rotate-45 rounded-[3rem] border border-dashed border-heritage/30" aria-hidden="true"></div>

        <a href="{{ route('dashboard') }}" class="relative flex w-fit items-center gap-3 rounded-lg">
            <x-logo class="size-12 rounded-[10px] shadow-[0_0_0_1px_rgb(255_255_255/0.18)]" />
            <span class="leading-tight"><span class="block text-lg font-bold">Kwv Tij Hmoob Xyooj</span><span class="block text-sm text-white/70">ລະບົບຄຸ້ມຄອງຕະກູນ</span></span>
        </a>

        <div class="relative my-auto max-w-lg py-10">
            <p class="text-[2.25rem] leading-snug font-bold">ເຊື່ອມໂຍງພີ່ນ້ອງ<br>ຮັກສາຮາກເຫງົ້າຂອງຕະກູນ</p>
            <ul class="mt-8 grid gap-4 sm:grid-cols-2">
                @foreach ($features as [$ic, $name, $text])
                    <li class="flex gap-3">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-heritage ring-1 ring-white/15">{{ icon($ic, 'size-5') }}</span>
                        <span class="leading-snug"><span class="block font-semibold">{{ $name }}</span><span class="text-sm text-white/70">{{ $text }}</span></span>
                    </li>
                @endforeach
            </ul>
        </div>

        <a href="{{ route('dashboard') }}" class="group relative inline-flex w-fit items-center gap-1.5 font-semibold text-white/90 hover:text-white">
            ເບິ່ງຂໍ້ມູນສາທາລະນະ ໂດຍບໍ່ຕ້ອງເຂົ້າສູ່ລະບົບ {{ icon('arrow-right', 'size-4 transition-transform group-hover:translate-x-1') }}
        </a>
    </section>

    <section class="flex items-center justify-center px-5 py-10 sm:px-10">
        <div class="w-full max-w-[27.5rem]">
            <a href="{{ route('dashboard') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                <x-logo class="size-10" /><span class="font-bold">Kwv Tij Hmoob Xyooj</span>
            </a>
            {{ $top ?? '' }}
            <h1 class="text-[1.75rem]">{{ $heading }}</h1>
            @if ($subheading)<p class="mt-1 text-muted">{{ $subheading }}</p>@endif
            {{ $slot }}
        </div>
    </section>
</div>
</x-layouts.public>
