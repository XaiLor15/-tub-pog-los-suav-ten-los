{{-- EmptyState: Lao message + optional action --}}
@props(['icon' => 'calendar', 'message' => 'ຍັງບໍ່ມີຂໍ້ມູນ'])
<div {{ $attributes->class(['flex flex-col items-center justify-center gap-3 px-4 py-12 text-center']) }}>
    <span class="inline-flex size-12 items-center justify-center rounded-full bg-surface-2 text-muted">{{ icon($icon, 'size-6') }}</span>
    <p class="text-[0.9375rem] text-muted">{{ $message }}</p>
    {{ $slot }}
</div>
