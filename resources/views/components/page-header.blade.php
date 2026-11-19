{{--
    PageHeader: optional icon tile · eyebrow slot · title · description · actions slot.
    <x-page-header title="ສະມາຊິກ" icon="users" description="…"><x-slot:actions>…</x-slot:actions></x-page-header>
--}}
@props(['title', 'description' => null, 'icon' => null])
<div {{ $attributes->class(['mb-6 flex flex-col gap-4 sm:mb-7 lg:flex-row lg:items-end lg:justify-between']) }}>
    <div class="flex min-w-0 items-start gap-4">
        @if ($icon)
            <span class="page-icon" aria-hidden="true">{{ icon($icon, 'size-6') }}</span>
        @endif
        <div class="min-w-0">
            {{ $eyebrow ?? '' }}
            <h1 class="text-foreground">{{ $title }}</h1>
            @if ($description)
                <p class="mt-1 max-w-2xl text-[0.9375rem] text-muted">{{ $description }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="no-print flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
