{{-- FilterChips: options [[value,label,count?]], current value; renders links keeping other query params. --}}
@props(['param', 'options', 'current' => '', 'label' => 'ຕົວກອງ'])
<div {{ $attributes->class(['flex flex-wrap gap-2']) }} role="group" aria-label="{{ $label }}">
    @foreach ($options as $opt)
        @php $active = (string) $current === (string) $opt['value']; @endphp
        <a href="{{ request()->fullUrlWithQuery([$param => $opt['value'] === '' ? null : $opt['value'], 'page' => null]) }}"
           class="chip" aria-pressed="{{ $active ? 'true' : 'false' }}">{{ $opt['label'] }}@isset($opt['count']) <span class="opacity-80">{{ $opt['count'] }}</span>@endisset</a>
    @endforeach
</div>
