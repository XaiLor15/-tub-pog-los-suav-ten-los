{{-- Toasts: success ("ບັນທຶກສຳເລັດ") / error. Animated in, auto-dismiss with a timer bar, closable. --}}
<div class="pointer-events-none fixed inset-x-0 bottom-20 z-[60] flex flex-col items-center gap-2 px-4 lg:bottom-6" aria-live="polite" id="toasts">
    @foreach (['success' => ['circle-check', 'bg-foreground text-background', 'text-success', 'status'], 'error' => ['circle-alert', 'bg-destructive text-white', 'text-white', 'alert']] as $type => [$ic, $cls, $icCls, $role])
        @if (session($type))
            <div class="toast pointer-events-auto flex max-w-md items-center gap-3 rounded-xl py-3 pr-2 pl-4 text-[0.9375rem] font-medium shadow-pop {{ $cls }}" data-autodismiss data-dismissable role="{{ $role }}">
                <span class="{{ $icCls }}">{{ icon($ic, 'size-5') }}</span>
                <span class="flex-1">{{ session($type) }}</span>
                <button type="button" class="inline-flex size-8 items-center justify-center rounded-lg opacity-70 transition hover:bg-white/10 hover:opacity-100" data-dismiss aria-label="ປິດ">{{ icon('x', 'size-4') }}</button>
            </div>
        @endif
    @endforeach
</div>
