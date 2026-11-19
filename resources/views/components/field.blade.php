@props(['name', 'label' => null, 'required' => false, 'hint' => null, 'class' => ''])
<div class="{{ $class }}">
    @if ($label)
        <label for="f-{{ $name }}" class="label">{{ $label }} @if ($required)<span class="text-destructive">*</span>@endif</label>
    @endif
    {{ $slot }}
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="error-text">{{ $message }}</p>@enderror
</div>
