{{--
    Panel: titled card section.
    <x-panel id="up-events" title="…" description="…" :href="route(…)" link-label="ທັງໝົດ">…</x-panel>
    Slot `actions` replaces the link when a section needs its own controls.
--}}
@props(['id', 'title', 'description' => null, 'icon' => null, 'href' => null, 'linkLabel' => 'ເບິ່ງທັງໝົດ', 'flush' => false])
<section {{ $attributes->class(['card flex flex-col']) }} aria-labelledby="{{ $id }}">
    <header class="flex flex-wrap items-start justify-between gap-x-4 gap-y-2 px-5 pt-5 {{ $flush ? 'pb-3' : 'pb-4' }}">
        <div class="flex min-w-0 items-center gap-3">
            @if ($icon)<span class="stat-icon size-8 rounded-lg">{{ icon($icon, 'size-4') }}</span>@endif
            <div class="min-w-0">
                <h2 id="{{ $id }}" class="text-section font-semibold">{{ $title }}</h2>
                @if ($description)<p class="text-caption text-muted">{{ $description }}</p>@endif
            </div>
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
        @elseif ($href)
            <a href="{{ $href }}" class="group inline-flex min-h-9 items-center gap-1 rounded-lg px-2 text-sm font-semibold text-primary transition-colors hover:bg-primary-soft">
                {{ $linkLabel }}{{ icon('arrow-right', 'size-4 transition-transform group-hover:translate-x-0.5') }}
            </a>
        @endisset
    </header>
    <div class="@container {{ $flush ? 'flex-1 px-3 pb-3' : 'flex-1 px-5 pb-5' }}">{{ $slot }}</div>
</section>
