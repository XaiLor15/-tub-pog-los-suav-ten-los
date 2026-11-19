{{--
    ErrorPage: shared layout for 403 / 404 / 419 / 429 / 500 / 503.
    <x-error-page code="404" icon="search-x" title="…" message="…">optional extra content</x-error-page>
    Slot `actions` replaces the default "back / home" buttons.
--}}
@props(['code', 'icon' => 'circle-alert', 'title', 'message' => null])
<x-layouts.public :title="$code.' · '.$title">
    <div class="flex justify-center px-4 py-14 sm:py-20">
        <div class="w-full max-w-xl text-center">
            <div class="relative mx-auto flex size-36 items-center justify-center" aria-hidden="true">
                <span class="absolute inset-4 rotate-45 rounded-2xl border border-primary/20 bg-primary-soft/60"></span>
                <span class="absolute inset-9 rotate-45 rounded-xl border-2 border-dashed border-heritage/70"></span>
                <span class="page-icon relative !inline-flex size-16 rounded-2xl">{{ icon($icon, 'size-7') }}</span>
            </div>
            <p class="num mt-4 text-display font-bold tracking-tight text-primary">{{ $code }}</p>
            <h1 class="mt-2 text-[1.625rem]">{{ $title }}</h1>
            @if ($message)<p class="mx-auto mt-3 max-w-md text-muted">{{ $message }}</p>@endif
            {{ $slot }}
            <div class="mt-7 flex flex-wrap justify-center gap-2">
                @isset($actions)
                    {{ $actions }}
                @else
                    <a href="{{ url()->previous() }}" class="btn-outline">{{ icon('refresh-cw', 'size-4') }} ລອງໃໝ່</a>
                    <a href="{{ route('dashboard') }}" class="btn-primary">{{ icon('layout-dashboard', 'size-4') }} ກັບໜ້າຫຼັກ</a>
                @endisset
            </div>
            <p class="mt-8 text-sm text-muted">ຖ້າບັນຫາຍັງເກີດຂຶ້ນ, ແຈ້ງຜູ້ດູແລລະບົບພ້ອມລະຫັດ <span class="num font-semibold text-foreground">{{ $code }}</span>.</p>
        </div>
    </div>
</x-layouts.public>
