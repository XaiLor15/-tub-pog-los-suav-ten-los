{{-- Toast ("ບັນທຶກສຳເລັດ") and error summary --}}
<div class="pointer-events-none fixed inset-x-0 bottom-20 z-50 flex flex-col items-center gap-2 px-4 lg:bottom-6" aria-live="polite" id="toasts">
    @if (session('success'))
        <div class="pointer-events-auto flex items-center gap-3 rounded-xl bg-foreground px-4 py-3 text-[0.9375rem] font-medium text-background shadow-pop" data-autodismiss role="status">
            <span class="text-success">{{ icon('circle-check', 'size-5') }}</span>{{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="pointer-events-auto flex items-center gap-3 rounded-xl bg-destructive px-4 py-3 text-[0.9375rem] font-medium text-white shadow-pop" data-autodismiss role="alert">
            {{ icon('circle-alert', 'size-5') }}{{ session('error') }}
        </div>
    @endif
</div>
