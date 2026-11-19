{{-- Family tree — artboard "FamilyTree". Layout, zoom/pan and lineage panel in resources/js/tree.js. --}}
<x-layouts.app title="ແຜນຜັງຕະກູນ" :crumbs="['ຕະກູນ', 'ແຜນຜັງຕະກູນ']">
    <x-page-header title="ແຜນຜັງຕະກູນ" description="ສາຍສືບທອດທາງຝ່າຍພໍ່ · ລູກຈັດລຽງຕາມລຳດັບການເກີດ">
        <x-slot:actions>
            <div class="segmented">
                <a href="{{ route('members.index') }}" class="segment">ຕາຕະລາງ</a>
                <a href="{{ route('members.index', ['view' => 'tree']) }}" class="segment" aria-current="page">ແຜນຜັງ</a>
            </div>
            <button type="button" class="btn-outline" data-tree-expand>ເປີດທຸກສາຍ</button>
            <button type="button" class="btn-outline" data-tree-lineage aria-pressed="false">ສະເພາະສາຍທີ່ເລືອກ</button>
            <button type="button" class="btn-outline" data-print>{{ icon('printer', 'size-4') }} ພິມ</button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-b border-border px-4 py-3 text-sm">
                <span class="flex items-center gap-2"><span class="size-4 rounded border-2 border-primary bg-primary-soft"></span>ທີ່ເລືອກ</span>
                <span class="flex items-center gap-2"><span class="h-0.5 w-6 bg-primary"></span>ສາຍບັນພະບຸລຸດ</span>
                <span class="flex items-center gap-2"><span class="size-3 rounded-full bg-avatar-male"></span>ລູກຊາຍ</span>
                <span class="flex items-center gap-2"><span class="size-3 rounded-full bg-avatar-female"></span>ລູກສາວ</span>
                <span class="flex items-center gap-2"><span class="rounded-full border border-foreground px-1.5 text-xs font-semibold">+3</span>ລູກທີ່ພັບໄວ້</span>
            </div>
            <div id="tree-viewport" class="tree-viewport relative h-[38rem] overflow-hidden bg-card select-none" tabindex="0" aria-label="ແຜນຜັງຕະກູນ — ລາກເພື່ອເລື່ອນ, ໃຊ້ +/− ເພື່ອຊູມ"
                 data-selected="{{ $selectedId }}" data-can-write="{{ $canWrite ? 1 : 0 }}" data-member-url="{{ url('/members') }}">
                <div id="tree-canvas" class="tree-canvas absolute top-0 left-0"></div>
                <div id="tree-status" class="absolute inset-0 flex items-center justify-center">
                    <div class="w-64 space-y-3"><div class="skeleton h-12"></div><div class="skeleton ml-8 h-12"></div><div class="skeleton ml-16 h-12"></div></div>
                </div>
                <div class="no-print absolute right-4 bottom-4 flex items-center gap-1 rounded-xl border border-border bg-card p-1 shadow-pop">
                    <button type="button" class="icon-btn-sm border-0" data-zoom="-1" aria-label="ຊູມອອກ">{{ icon('minus', 'size-4') }}</button>
                    <span class="num w-12 text-center text-sm" data-zoom-label>100%</span>
                    <button type="button" class="icon-btn-sm border-0" data-zoom="1" aria-label="ຊູມເຂົ້າ">{{ icon('plus', 'size-4') }}</button>
                    <button type="button" class="icon-btn-sm border-0" data-zoom-fit aria-label="ພໍດີໜ້າຈໍ">{{ icon('maximize', 'size-4') }}</button>
                </div>
            </div>
        </div>

        <aside id="lineage-panel" class="card xl:sticky xl:top-24" aria-live="polite">
            <div class="p-5"><p class="text-sm text-muted">ເລືອກສະມາຊິກໃນແຜນຜັງ ເພື່ອເບິ່ງສາຍບັນພະບຸລຸດ</p></div>
        </aside>
    </div>
</x-layouts.app>
