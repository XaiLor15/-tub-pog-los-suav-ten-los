@auth
    @if (auth()->user()->isPending())
        <div {{ $attributes->class(['mb-6 flex items-start gap-3 rounded-xl border border-warning/30 bg-warning-bg px-4 py-3 text-warning']) }} role="status">
            {{ icon('clock', 'mt-0.5 size-5') }}
            <div>
                <p class="font-semibold">ບັນຊີຂອງທ່ານກຳລັງລໍຖ້າອະນຸມັດ</p>
                <p class="text-[0.875rem]">ຜູ້ດູແລລະບົບຈະກວດສອບ ແລະ ເຊື່ອມບັນຊີກັບຂໍ້ມູນສະມາຊິກ (ປົກກະຕິ 1–2 ວັນ). ລະຫວ່າງນີ້ທ່ານເບິ່ງຂໍ້ມູນສາທາລະນະໄດ້.</p>
            </div>
        </div>
    @endif
@endauth
