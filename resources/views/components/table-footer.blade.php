@props(['paginator'])
<div class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-[0.8125rem] text-muted sm:flex-row">
    <span>ສະແດງ {{ $paginator->count() }} ລາຍການ · ໜ້າ {{ $paginator->currentPage() }} ຈາກ {{ max(1, $paginator->lastPage()) }}</span>
    {{ $paginator->onEachSide(1)->links() }}
</div>
