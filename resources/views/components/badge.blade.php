{{-- StatusBadge — tone: success | warning | destructive | info | neutral | primary · dot: leading status dot --}}
@props(['tone' => 'neutral', 'dot' => false])
<span {{ $attributes->class(['badge', tone_classes($tone)]) }}>@if ($dot)<span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>@endif{{ $slot }}</span>
