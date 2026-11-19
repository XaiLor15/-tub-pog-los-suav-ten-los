@php $tone = \App\Services\AuditLogger::tone($log->action); @endphp
<li class="relative flex gap-3 py-3 pl-1 before:absolute before:top-0 before:bottom-0 before:left-[0.5625rem] before:w-px before:bg-border first:before:top-5 last:before:bottom-auto last:before:h-5">
    <span class="relative mt-1.5 size-3 shrink-0 rounded-full ring-4 ring-card {{ match ($tone) { 'success' => 'bg-success', 'destructive' => 'bg-destructive', 'primary' => 'bg-primary', default => 'bg-info' } }}" aria-hidden="true"></span>
    <span class="min-w-0">
        <span class="block font-semibold">{{ $log->actor?->display_name ?? 'ລະບົບ' }} <span class="font-normal">{{ \App\Services\AuditLogger::label($log->action) }}</span> {{ $log->summary }}</span>
        <span class="text-[0.8125rem] text-muted">{{ lao_date($log->created_at, true) }} · {{ \App\Services\AuditLogger::module($log->action) }}</span>
    </span>
</li>
