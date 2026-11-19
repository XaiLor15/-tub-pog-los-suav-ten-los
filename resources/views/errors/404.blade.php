<x-layouts.public title="ບໍ່ພົບໜ້າ">
<div class="flex flex-col items-center px-4 py-16 text-center sm:py-24">
    <div class="relative flex size-32 items-center justify-center">
        <span class="absolute inset-3 rotate-45 rounded-md border-2 border-primary-soft"></span>
        <span class="absolute inset-9 rotate-45 border-2 border-dashed border-heritage"></span>
        <span class="relative text-[2.75rem] font-bold text-primary">404</span>
    </div>
    <h1 class="mt-6 text-[1.75rem]">ບໍ່ພົບໜ້າທີ່ທ່ານຊອກຫາ</h1>
    <p class="mt-2 text-muted">ລິ້ງອາດຈະຜິດ, ໜ້າຖືກຍ້າຍ, ຫຼື ຂໍ້ມູນນີ້ຖືກລຶບແລ້ວ. ລອງຄົ້ນຫາ ຫຼື ກັບໄປໜ້າຫຼັກ.</p>
    <form action="{{ route('members.index') }}" method="GET" class="mt-6 flex w-full max-w-xl gap-2">
        <input name="q" class="input flex-1" placeholder="ຄົ້ນຫາສະມາຊິກ ຫຼື ໜ້າ..." aria-label="ຄົ້ນຫາ">
        <button class="btn-primary">ຄົ້ນຫາ</button>
    </form>
    <div class="mt-4 flex flex-wrap justify-center gap-2">
        <a href="{{ route('dashboard') }}" class="btn-outline">ກັບໜ້າຫຼັກ</a>
        <a href="{{ route('members.index') }}" class="btn-outline">ສະມາຊິກ</a>
        <a href="{{ route('activities.index') }}" class="btn-outline">ກິດຈະກຳ</a>
    </div>
    <p class="mt-6 text-sm text-muted">ຖ້າທ່ານຄິດວ່ານີ້ແມ່ນບັນຫາ, ແຈ້ງຜູ້ດູແລລະບົບພ້ອມລິ້ງທີ່ທ່ານເປີດ.</p>
</div>
</x-layouts.public>
