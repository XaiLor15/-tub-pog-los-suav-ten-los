{{-- StatusBadge — tone: success | warning | destructive | info | neutral | primary --}}
@props(['tone' => 'neutral'])
<span {{ $attributes->class(['badge', tone_classes($tone)]) }}>{{ $slot }}</span>
