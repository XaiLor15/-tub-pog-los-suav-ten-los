{{--
    ProfileHero: heritage band + overlapping avatar, used at the top of detail panels and the profile page.
    Slots: avatar (required), badges, actions (rendered on the band), default slot (extra lines under the name).
--}}
@props(['title', 'subtitle' => null, 'headingLevel' => 'h2'])
<div {{ $attributes->class(['relative']) }}>
    <div class="hero-band h-24 sm:h-28" aria-hidden="true"></div>
    @isset($actions)
        <div class="absolute top-3 right-3 flex gap-1.5">{{ $actions }}</div>
    @endisset
    <div class="relative -mt-12 px-5 pb-5 sm:px-6">
        <div class="hero-avatar inline-flex rounded-full">{{ $avatar }}</div>
        <{{ $headingLevel }} class="mt-3 text-[1.375rem] leading-tight font-bold">{{ $title }}</{{ $headingLevel }}>
        @if ($subtitle)<p class="mt-0.5 text-sm text-muted">{{ $subtitle }}</p>@endif
        @isset($badges)<div class="mt-2.5 flex flex-wrap gap-1.5">{{ $badges }}</div>@endisset
        {{ $slot }}
    </div>
</div>
