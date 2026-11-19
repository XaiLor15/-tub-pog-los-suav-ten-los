{{-- PageHeader: title, description, actions slot --}}
@props(['title', 'description' => null])
<div {{ $attributes->class(['mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        {{ $eyebrow ?? '' }}
        <h1 class="text-foreground">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-2xl text-[0.9375rem] text-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="no-print flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
