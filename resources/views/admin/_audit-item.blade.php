@php $tone = \App\Services\AuditLogger::tone($log->action); @endphp
<li class="flex gap-3 border-b border-border py-3 last:border-0">
    <span class="mt-2 size-2.5 shrink-0 rounded-full {{ match ($tone) { 'success' => 'bg-success', 'destructive' => 'bg-destructive', 'primary' => 'bg-primary', default => 'bg-info' } }}" aria-hidden="true"></span>
    <span class="min-w-0">
        <span class="block font-semibold">{{ $log->actor?->display_name ?? 'ລະບົບ' }} <span class="font-normal">{{ \App\Services\AuditLogger::label($log->action) }}</span> {{ $log->summary }}</span>
        <span class="text-[0.8125rem] text-muted">{{ lao_date($log->created_at, true) }} · {{ \App\Services\AuditLogger::module($log->action) }}</span>
    </span>
</li>
