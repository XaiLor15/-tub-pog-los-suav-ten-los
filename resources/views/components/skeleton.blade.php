{{-- LoadingState: skeleton rows. --}}
@props(['rows' => 3])
<div {{ $attributes->class(['space-y-3 p-4']) }} aria-busy="true" aria-label="ກຳລັງໂຫຼດ">
    @for ($i = 0; $i < $rows; $i++)
        <div class="flex items-center gap-3"><div class="skeleton size-10 rounded-full"></div><div class="flex-1 space-y-2"><div class="skeleton h-3.5 w-2/3"></div><div class="skeleton h-3 w-1/3"></div></div></div>
    @endfor
</div>
