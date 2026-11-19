@php $ev = $editing; @endphp
<x-dialog id="event-form" :title="$ev ? 'ແກ້ໄຂກິດຈະກຳ' : 'ສ້າງກິດຈະກຳ'" subtitle="ຊ່ອງທີ່ມີ * ຕ້ອງໃສ່" :open="$open">
    <form method="POST" action="{{ $ev ? route('activities.update', $ev) : route('activities.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col" data-busy>
        @csrf @if ($ev) @method('PUT') @endif <input type="hidden" name="_form" value="event">
        <div class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
            <x-field name="title" label="ຊື່ກິດຈະກຳ" required><input id="f-title" name="title" value="{{ old('title', $ev?->title) }}" maxlength="200" class="input @error('title') input-error @enderror"></x-field>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="category" label="ໝວດ" required><x-select name="category" :options="array_combine(\App\Support\Lookups::EVENT_CATEGORIES, \App\Support\Lookups::EVENT_CATEGORIES)" :selected="old('category', $ev?->category)" /></x-field>
                <div><span class="label">ການເຜີຍແຜ່</span>
                    <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="is_public" value="1" class="size-4" @checked(old('is_public', $ev?->is_public))> ສາທາລະນະ (Guest ເບິ່ງໄດ້)</label></div>
                <x-field name="start_date" label="ວັນເລີ່ມ" required><input id="f-start_date" type="date" name="start_date" value="{{ old('start_date', $ev?->start_date?->toDateString()) }}" class="input @error('start_date') input-error @enderror"></x-field>
                <x-field name="end_date" label="ວັນສິ້ນສຸດ"><input id="f-end_date" type="date" name="end_date" value="{{ old('end_date', $ev?->end_date?->toDateString()) }}" class="input @error('end_date') input-error @enderror"></x-field>
                <x-field name="start_time" label="ເວລາເລີ່ມ"><input id="f-start_time" type="time" name="start_time" value="{{ old('start_time', $ev?->start_time ? substr($ev->start_time, 0, 5) : '') }}" class="input"></x-field>
                <x-field name="end_time" label="ເວລາສິ້ນສຸດ"><input id="f-end_time" type="time" name="end_time" value="{{ old('end_time', $ev?->end_time ? substr($ev->end_time, 0, 5) : '') }}" class="input"></x-field>
                <x-field name="location" label="ສະຖານທີ່"><input id="f-location" name="location" value="{{ old('location', $ev?->location) }}" maxlength="200" class="input"></x-field>
                <x-field name="organizer" label="ຜູ້ຈັດ"><input id="f-organizer" name="organizer" value="{{ old('organizer', $ev?->organizer) }}" maxlength="150" class="input"></x-field>
            </div>
            <x-field name="description" label="ລາຍລະອຽດ"><textarea id="f-description" name="description" rows="4" maxlength="5000" class="textarea">{{ old('description', $ev?->description) }}</textarea></x-field>
            <x-field name="cover" label="ຮູບໜ້າປົກ"><input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="input py-2" data-preview="ev-cover" data-max-mb="10"><p id="ev-cover" class="hint">JPG, PNG, WebP · ບໍ່ເກີນ 10 MB</p></x-field>
        </div>
        <div class="flex justify-end gap-2 border-t border-border px-5 py-4 sm:px-6">
            <button type="button" class="btn-outline" data-modal-close>ຍົກເລີກ</button>
            <button class="btn-primary">ບັນທຶກ</button>
        </div>
    </form>
</x-dialog>
