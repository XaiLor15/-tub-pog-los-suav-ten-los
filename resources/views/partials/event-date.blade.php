<span class="flex size-[3.25rem] shrink-0 flex-col items-center justify-center rounded-lg border border-border bg-primary-soft/40 leading-none">
    <span class="num text-lg font-bold text-primary">{{ $date->format('d') }}</span>
    <span class="mt-1 text-[0.6875rem] text-muted">{{ \App\Support\Format::monthName($date->month) }}</span>
</span>
