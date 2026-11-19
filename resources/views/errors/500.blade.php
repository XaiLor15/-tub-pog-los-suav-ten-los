<x-layouts.public title="500">
<div class="flex justify-center px-4 py-24">
    <div class="card w-full max-w-lg px-8 py-10 text-center">
        <p class="text-[2.75rem] font-bold text-primary">500</p>
        <h1 class="mt-2 text-[1.5rem]">ເກີດຂໍ້ຜິດພາດໃນລະບົບ</h1>
        <div class="mt-6 flex justify-center gap-2">
            <a href="{{ url()->previous() }}" class="btn-outline">ລອງໃໝ່</a>
            <a href="{{ route('dashboard') }}" class="btn-primary">ກັບໜ້າຫຼັກ</a>
        </div>
    </div>
</div>
</x-layouts.public>
