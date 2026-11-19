<span class="flex size-[3.25rem] shrink-0 flex-col items-center justify-center rounded-lg border border-border bg-gradient-to-b from-primary-soft/70 to-card leading-none transition-transform duration-300 group-hover:scale-105">
    <span class="num text-lg font-bold text-primary">{{ $date->format('d') }}</span>
    <span class="mt-1 text-[0.6875rem] text-muted">{{ \App\Support\Format::monthName($date->month) }}</span>
</span>
