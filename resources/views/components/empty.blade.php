{{-- EmptyState: icon + Lao message + optional first action (slot). --}}
@props(['icon' => 'inbox', 'message' => 'ຍັງບໍ່ມີຂໍ້ມູນ', 'hint' => null])
<div {{ $attributes->class(['flex flex-col items-center justify-center gap-3 px-4 py-12 text-center']) }} role="status">
    <span class="state-icon">{{ icon($icon, 'size-6') }}</span>
    <div>
        <p class="text-[0.9375rem] font-medium text-foreground">{{ $message }}</p>
        @if ($hint)<p class="mt-0.5 text-sm text-muted">{{ $hint }}</p>@endif
    </div>
    {{ $slot }}
</div>
