{{-- MoneyText: amount with optional +/− sign coloured by type --}}
@props(['amount', 'sign' => null])
@php
    $cls = match ($sign) { '+' => 'text-success', '-' => 'text-destructive', default => '' };
    $text = $sign ? ($sign === '-' ? '− ' : '+ ').money(abs((int) $amount)) : money($amount);
@endphp
<span {{ $attributes->class(['num whitespace-nowrap', $cls]) }}>{{ $text }}</span>
