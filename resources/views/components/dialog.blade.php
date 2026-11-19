{{-- FormDialog / generic modal. Open with data-modal-open="{id}". On mobile it becomes a full-height sheet. --}}
@props(['id', 'title', 'subtitle' => null, 'size' => 'md', 'open' => false])
@php $w = ['sm' => 'sm:max-w-md', 'md' => 'sm:max-w-xl', 'lg' => 'sm:max-w-2xl', 'xl' => 'sm:max-w-4xl'][$size] ?? 'sm:max-w-xl'; @endphp
<dialog id="{{ $id }}" class="sheet m-0 h-full w-full sm:m-auto sm:h-auto sm:w-[calc(100%-2rem)] {{ $w }}" aria-labelledby="{{ $id }}-title" @if ($open) data-autoopen @endif>
    <div class="dialog-panel flex h-full max-h-dvh flex-col bg-card text-foreground shadow-pop sm:max-h-[calc(100dvh-4rem)] sm:rounded-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-border px-5 py-4 sm:px-6">
            <div>
                <h2 id="{{ $id }}-title" class="text-lg font-bold">{{ $title }}</h2>
                @if ($subtitle)<p class="text-[0.8125rem] text-muted">{{ $subtitle }}</p>@endif
            </div>
            <button type="button" class="icon-btn-sm border-0" data-modal-close aria-label="ປິດ">{{ icon('x', 'size-5') }}</button>
        </div>
        {{ $slot }}
    </div>
</dialog>
