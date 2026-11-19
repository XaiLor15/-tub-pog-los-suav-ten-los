@props(['name', 'options' => [], 'selected' => null, 'placeholder' => null, 'id' => null])
{{-- id defaults to f-{name} (matches x-field's label); pass a unique id when the same name appears twice on a page. --}}
<div class="relative">
    <select name="{{ $name }}" id="{{ $id ?? 'f-'.$name }}" {{ $attributes->class(['input appearance-none pr-9 cursor-pointer', 'input-error' => $errors->has($name)]) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-muted">{{ icon('chevron-down', 'size-4') }}</span>
</div>
