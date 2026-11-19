{{-- CommandSearch (Ctrl/Cmd + K). Results come from GET /search (filtered by the viewer's role). --}}
<dialog id="search-dialog" class="m-0 mx-auto mt-0 w-full max-w-2xl bg-transparent sm:mt-[10vh] sm:w-[calc(100%-2rem)]" aria-label="ຄົ້ນຫາ">
    <div class="dialog-panel flex max-h-dvh flex-col overflow-hidden bg-card text-foreground shadow-pop sm:max-h-[75vh] sm:rounded-2xl">
        <div class="flex items-center gap-3 border-b border-border px-4 py-3">
            <span class="text-muted">{{ icon('search', 'size-5') }}</span>
            <input id="search-input" type="search" autocomplete="off" class="h-11 flex-1 bg-transparent text-lg outline-none placeholder:text-muted"
                   placeholder="ຄົ້ນຫາສະມາຊິກ (ລາວ / ມົ້ງ), ກິດຈະກຳ, ໜ້າ..." role="combobox" aria-expanded="true" aria-controls="search-results" aria-autocomplete="list">
            <button type="button" class="icon-btn-sm border-0 bg-surface-2" data-search-clear aria-label="ລ້າງ">{{ icon('x', 'size-4') }}</button>
            <kbd class="hidden rounded border border-border px-1.5 py-0.5 text-xs text-muted sm:inline">Esc</kbd>
        </div>
        <div id="search-results" class="flex-1 overflow-y-auto p-2" role="listbox" aria-label="ຜົນການຄົ້ນຫາ"></div>
        <div class="flex items-center justify-between gap-3 border-t border-border bg-surface-2/50 px-4 py-2.5 text-xs text-muted">
            <span class="hidden items-center gap-1.5 sm:flex"><kbd class="rounded border border-border bg-card px-1">↑</kbd><kbd class="rounded border border-border bg-card px-1">↓</kbd> ເລືອກ <kbd class="ml-2 rounded border border-border bg-card px-1.5">Enter</kbd> ເປີດ</span>
            <span>ຜົນສະແດງຕາມສິດຂອງທ່ານ</span>
        </div>
    </div>
</dialog>
