{{-- Notifications popover (420px). Data: GET /notifications; read state in notification_reads. --}}
<div id="notif-panel" role="dialog" aria-label="ແຈ້ງເຕືອນ" hidden
     class="fixed inset-x-2 top-[4.5rem] z-40 flex max-h-[75vh] flex-col overflow-hidden rounded-2xl border border-border bg-card shadow-pop sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-[26.25rem]">
    <div class="flex items-center justify-between px-5 pt-4">
        <h2 class="text-lg font-bold">ແຈ້ງເຕືອນ</h2>
        <button type="button" class="btn-ghost btn-sm" id="notif-mark-all">ໝາຍວ່າອ່ານທັງໝົດ</button>
    </div>
    <div class="mt-2 flex gap-4 border-b border-border px-5" role="tablist">
        <button type="button" role="tab" class="border-b-2 border-primary py-2 text-sm font-semibold" aria-selected="true" data-notif-tab="all">ທັງໝົດ</button>
        <button type="button" role="tab" class="border-b-2 border-transparent py-2 text-sm font-semibold text-muted" aria-selected="false" data-notif-tab="unread">ຍັງບໍ່ອ່ານ (<span data-notif-unread>0</span>)</button>
    </div>
    <div id="notif-list" class="flex-1 overflow-y-auto p-2">
        <div class="space-y-2 p-3"><div class="skeleton h-4 w-3/4"></div><div class="skeleton h-4 w-1/2"></div></div>
    </div>
</div>
