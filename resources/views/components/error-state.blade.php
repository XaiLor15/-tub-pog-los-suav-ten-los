{{-- ErrorState: Lao message + "ລອງໃໝ່" (reload by default). --}}
@props(['message' => 'ບໍ່ສາມາດເຊື່ອມຕໍ່ເຄືອຂ່າຍໄດ້', 'retry' => null])
<div {{ $attributes->class(['flex flex-col items-center justify-center gap-3 px-4 py-12 text-center']) }} role="alert">
    <span class="state-icon is-error">{{ icon('wifi-off', 'size-6') }}</span>
    <p class="text-[0.9375rem] font-medium text-destructive">{{ $message }}</p>
    <a href="{{ $retry ?? url()->current() }}" class="btn-outline btn-sm">{{ icon('refresh-cw', 'size-4') }} ລອງໃໝ່</a>
</div>
