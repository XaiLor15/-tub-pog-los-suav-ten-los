{{-- Unauthorized — 3 cases: guest (sign in & come back), pending (awaiting approval), role (403) --}}
@php
    $reason = in_array(request('reason'), ['guest', 'pending', 'role'], true) ? request('reason') : (auth()->check() ? 'role' : 'guest');
    $from = \App\Http\Controllers\Auth\LoginController::safeRedirect(request('from'));
    $pages = ['/finance' => 'ການເງິນ', '/fund-contributors' => 'ຜູ້ຮ່ວມກອງທຶນ', '/donation-tracking' => 'ຕິດຕາມການບໍລິຈາກ', '/admin' => 'ຈັດການຜູ້ໃຊ້', '/profile' => 'ໂປຣໄຟລ໌'];
    $page = collect($pages)->first(fn ($v, $k) => $from && str_starts_with($from, $k));
@endphp
<x-layouts.public title="ບໍ່ມີສິດ">
<div class="flex justify-center px-4 py-16 sm:py-24">
    <div class="card w-full max-w-[39rem] overflow-hidden text-center">
        <div class="hero-band h-20" aria-hidden="true"></div>
        <div class="px-6 pb-10 sm:px-14">
        <span class="page-icon hero-avatar relative -mt-9 !inline-flex size-[4.5rem] rounded-2xl">{{ icon($reason === 'pending' ? 'clock' : ($reason === 'guest' ? 'key-round' : 'lock'), 'size-8') }}</span>
        @if ($page)<p class="mt-4 text-sm font-semibold text-muted">ໜ້າ: {{ $page }}</p>@endif
        @if ($reason === 'guest')
            <h1 class="mt-2 text-[1.625rem]">ກະລຸນາເຂົ້າສູ່ລະບົບ</h1>
            <p class="mt-3 text-muted">ໜ້ານີ້ສະເພາະສະມາຊິກຕະກູນ. ເຂົ້າສູ່ລະບົບແລ້ວລະບົບຈະພາທ່ານກັບມາໜ້ານີ້ອັດຕະໂນມັດ.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('login', array_filter(['redirect' => $from])) }}" class="btn-primary">ເຂົ້າສູ່ລະບົບ</a>
                <a href="{{ route('dashboard') }}" class="btn-outline">ກັບໜ້າຫຼັກ</a>
            </div>
            <p class="mt-6 text-sm text-muted">ຍັງບໍ່ມີບັນຊີ? ລົງທະບຽນໄດ້ຢູ່ໜ້າເຂົ້າສູ່ລະບົບ.</p>
        @elseif ($reason === 'pending')
            <h1 class="mt-2 text-[1.625rem]">ບັນຊີກຳລັງລໍຖ້າອະນຸມັດ</h1>
            <p class="mt-3 text-muted">ຜູ້ດູແລລະບົບຈະກວດສອບ ແລະ ອະນຸມັດບັນຊີຂອງທ່ານ (ປົກກະຕິ 1–2 ວັນ). ຫຼັງອະນຸມັດທ່ານຈະເຂົ້າໜ້ານີ້ໄດ້.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('profile') }}" class="btn-primary">ເບິ່ງໂປຣໄຟລ໌</a>
                <a href="{{ route('dashboard') }}" class="btn-outline">ກັບໜ້າຫຼັກ</a>
            </div>
        @else
            <h1 class="mt-2 text-[1.625rem]">ທ່ານບໍ່ມີສິດເຂົ້າໜ້ານີ້</h1>
            <p class="mt-3 text-muted">ໜ້ານີ້ສະເພາະຜູ້ດູແລລະບົບ. ຖ້າຕ້ອງການສິດ ກະລຸນາຕິດຕໍ່ຄະນະນຳຕະກູນ.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('leadership.index') }}" class="btn-primary">ຕິດຕໍ່ຄະນະນຳ</a>
                <a href="{{ route('dashboard') }}" class="btn-outline">ກັບໜ້າຫຼັກ</a>
            </div>
        @endif
        </div>
    </div>
</div>
</x-layouts.public>
