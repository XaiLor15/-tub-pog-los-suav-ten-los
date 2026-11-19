{{-- ConfirmDialog used by <form data-confirm="..."> (every delete goes through it). --}}
<dialog id="confirm-dialog" class="m-auto w-[calc(100%-2rem)] max-w-md" aria-labelledby="confirm-title">
    <form method="dialog" class="dialog-panel rounded-2xl bg-card p-6 text-foreground shadow-pop">
        <h2 id="confirm-title" class="text-lg font-bold">ຢືນຢັນ</h2>
        <p class="mt-2 text-[0.9375rem] text-muted" data-confirm-message></p>
        <div class="mt-6 flex justify-end gap-2">
            <button value="cancel" class="btn-outline">ຍົກເລີກ</button>
            <button value="ok" class="btn-primary" data-confirm-ok>ຢືນຢັນ</button>
        </div>
    </form>
</dialog>
