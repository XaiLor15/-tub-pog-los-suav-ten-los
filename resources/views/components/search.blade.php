{{--
    Search field for GET filter forms: leading icon, clear button when filled (clears and resubmits the form).
    <x-search placeholder="ຄົ້ນຫາຊື່" class="lg:w-80" />
--}}
@props(['name' => 'q', 'placeholder' => 'ຄົ້ນຫາ', 'label' => null, 'value' => null])
@php $value ??= request($name); @endphp
<label {{ $attributes->class(['relative block min-w-0']) }}>
    <span class="sr-only">{{ $label ?? $placeholder }}</span>
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">{{ icon('search', 'size-4') }}</span>
    <input type="search" name="{{ $name }}" value="{{ $value }}" class="input pr-10 pl-9" placeholder="{{ $placeholder }}" enterkeyhint="search" autocomplete="off">
    @if (filled($value))
        <button type="button" class="absolute inset-y-0 right-1 my-auto inline-flex size-9 items-center justify-center rounded-md text-muted transition-colors hover:bg-surface-2 hover:text-foreground" data-clear-search aria-label="ລ້າງການຄົ້ນຫາ">{{ icon('x', 'size-4') }}</button>
    @endif
</label>
