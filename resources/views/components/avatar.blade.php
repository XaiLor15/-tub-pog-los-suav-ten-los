{{-- MemberAvatar: photo or Lao initial; colour by sex; greyed when deceased. --}}
@props(['member' => null, 'name' => null, 'sex' => null, 'size' => 'md', 'url' => null, 'dead' => null])
@php
    $name ??= $member?->name ?? '?';
    $sex ??= $member?->sex ?? 'male';
    $url ??= $member?->photoUrl();
    $dead ??= $member ? ! $member->is_alive : false;
    $sizes = ['xs' => 'size-7 text-xs', 'sm' => 'size-9 text-sm', 'md' => 'size-11 text-base', 'lg' => 'size-16 text-xl', 'xl' => 'size-20 text-2xl'];
    $color = $dead ? 'bg-surface-2 text-muted' : ($sex === 'female' ? 'bg-avatar-female text-avatar-female-fg' : 'bg-avatar-male text-avatar-male-fg');
@endphp
<span {{ $attributes->class(['relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-bold', $sizes[$size] ?? $sizes['md'], $color]) }}>
    @if ($url)
        <img src="{{ $url }}" alt="" class="size-full object-cover {{ $dead ? 'grayscale' : '' }}" loading="lazy">
    @else
        <span aria-hidden="true">{{ initial($name) }}</span>
    @endif
</span>
