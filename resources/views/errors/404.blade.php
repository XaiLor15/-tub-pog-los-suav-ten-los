<x-error-page code="404" icon="search-x" title="ບໍ່ພົບໜ້າທີ່ທ່ານຊອກຫາ" message="ລິ້ງອາດຈະຜິດ, ໜ້າຖືກຍ້າຍ, ຫຼື ຂໍ້ມູນນີ້ຖືກລຶບແລ້ວ. ລອງຄົ້ນຫາ ຫຼື ໄປໜ້າທີ່ໃຊ້ເລື້ອຍໆ.">
    <form action="{{ route('members.index') }}" method="GET" class="mx-auto mt-6 flex max-w-md gap-2">
        <x-search placeholder="ຄົ້ນຫາສະມາຊິກ..." class="flex-1" />
        <button class="btn-primary">ຄົ້ນຫາ</button>
    </form>
    <x-slot:actions>
        <a href="{{ route('dashboard') }}" class="btn-outline">{{ icon('layout-dashboard', 'size-4') }} ໜ້າຫຼັກ</a>
        <a href="{{ route('members.index') }}" class="btn-outline">{{ icon('users', 'size-4') }} ສະມາຊິກ</a>
        <a href="{{ route('activities.index') }}" class="btn-outline">{{ icon('calendar-days', 'size-4') }} ກິດຈະກຳ</a>
    </x-slot:actions>
</x-error-page>
