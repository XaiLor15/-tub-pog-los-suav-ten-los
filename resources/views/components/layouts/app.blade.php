{{-- AppLayout: navy collapsible sidebar · glass header (search, theme, notifications, account) · mobile bottom nav. --}}
@props(['title' => null, 'crumbs' => []])
@php
    $user = auth()->user();
    $groups = \App\View\Navigation::groups($user);
    $themes = ['light' => ['sun', 'ແຈ້ງ'], 'dark' => ['moon', 'ມືດ'], 'system' => ['monitor', 'ຕາມອຸປະກອນ']];
@endphp
<!DOCTYPE html>
<html lang="lo" data-theme-pref="{{ $user?->theme ?? 'system' }}" data-font-scale="{{ $user?->font_scale ?? 100 }}" @guest data-guest @endguest>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="view-transition" content="same-origin">
    <meta name="theme-color" content="#1A2B4F">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script src="{{ asset('js/theme-init.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh" data-auth="{{ $user ? $user->role : 'guest' }}">
{{ icon_sprite(\App\View\Navigation::SPRITE) }}
<div id="page-progress" aria-hidden="true"></div>
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-card focus:px-4 focus:py-2">ໄປຫາເນື້ອຫາ</a>

{{-- Sidebar: fixed on desktop (collapsible to an icon rail), off-canvas drawer below lg --}}
<div id="drawer-backdrop" class="fixed inset-0 z-40 hidden bg-black/45 backdrop-blur-[2px] lg:hidden" data-drawer-close></div>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex -translate-x-full flex-col lg:translate-x-0" aria-label="ເມນູຫຼັກ">
    <div class="brand flex h-[var(--header-h)] shrink-0 items-center gap-3 px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-lg focus-visible:outline-heritage" data-tip="Kwv Tij Hmoob Xyooj" data-tip-when="collapsed" data-tip-side="right">
            <x-logo class="size-10 shrink-0 rounded-[9px] shadow-[0_0_0_1px_rgb(255_255_255/0.14),0_6px_16px_-6px_rgb(0_0_0/0.5)]" />
            <span class="brand-text leading-tight">
                <span class="block text-[0.9375rem] font-bold text-white">Kwv Tij Hmoob Xyooj</span>
                <span class="block text-xs text-white/60">ລະບົບຄຸ້ມຄອງຕະກູນ</span>
            </span>
        </a>
    </div>

    <nav class="flex-1 overflow-x-hidden overflow-y-auto px-3 pt-2 pb-4" aria-label="ເມນູ">
        @foreach ($groups as $group)
            <p class="side-group" id="nav-g{{ $loop->index }}">{{ $group['label'] }}</p>
            <ul class="space-y-0.5" aria-labelledby="nav-g{{ $loop->index }}">
                @foreach ($group['items'] as $item)
                    <li>
                        <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="side-link" data-tip="{{ $item['label'] }}" data-tip-when="collapsed" data-tip-side="right"
                           @if (\App\View\Navigation::isActive($item)) aria-current="page" @endif>
                            {{ icon($item['icon'], 'size-5 shrink-0') }}
                            <span class="nav-label flex-1">{{ $item['label'] }}</span>
                            @if (! empty($item['badge']))
                                <span class="nav-badge badge bg-heritage px-2 text-xs text-brand-navy" aria-label="{{ $item['badge'] }} ລໍຖ້າອະນຸມັດ">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    <div class="sidebar-divider space-y-1.5 border-t p-3">
        @auth
            <a href="{{ route('profile') }}" class="user-card flex items-center gap-3 rounded-xl border p-2.5" data-tip="{{ $user->display_name }}" data-tip-when="collapsed" data-tip-side="right"
               @if (request()->routeIs('profile*')) aria-current="page" @endif>
                <x-avatar :name="$user->display_name" :url="$user->avatarUrl()" size="sm" class="!bg-heritage !text-brand-navy" />
                <span class="user-text min-w-0 flex-1 leading-tight">
                    <span class="block truncate text-sm font-semibold text-white">{{ $user->display_name }}</span>
                    <span class="block truncate text-xs text-white/60">{{ $user->roleLabel() }}</span>
                </span>
            </a>
        @else
            <a href="{{ route('login') }}" class="btn w-full bg-heritage text-brand-navy hover:brightness-105" data-tip="ເຂົ້າສູ່ລະບົບ" data-tip-when="collapsed" data-tip-side="right">{{ icon('user-round', 'size-4') }}<span class="nav-label">ເຂົ້າສູ່ລະບົບ</span></a>
        @endauth
        <button type="button" class="collapse-toggle side-link hidden w-full lg:flex" data-sidebar-toggle aria-label="ຫຍໍ້ເມນູ" data-tip="ຂະຫຍາຍເມນູ" data-tip-when="collapsed" data-tip-side="right">
            {{ icon('panel-left-close', 'size-5 shrink-0') }}<span class="nav-label flex-1 text-left text-sm">ຫຍໍ້ເມນູ</span>
        </button>
    </div>
</aside>

<div class="app-content">
    <header class="app-header glass no-print sticky top-0 z-30 flex h-[var(--header-h)] items-center gap-2 border-b border-border/70 px-4 sm:gap-3 sm:px-6 lg:px-8">
        <button type="button" class="icon-btn lg:hidden" data-drawer-open aria-label="ເປີດເມນູ" aria-controls="sidebar">{{ icon('menu') }}</button>

        <nav aria-label="Breadcrumb" class="hidden min-w-0 flex-1 text-sm md:block">
            <ol class="flex items-center gap-1.5 truncate">
                @foreach ($crumbs as $i => $crumb)
                    @if ($i > 0)<li class="text-muted/70" aria-hidden="true">{{ icon('chevron-right', 'size-3.5') }}</li>@endif
                    <li class="{{ $loop->last ? 'font-semibold text-foreground' : 'text-muted' }} truncate" @if ($loop->last) aria-current="page" @endif>{{ $crumb }}</li>
                @endforeach
            </ol>
        </nav>
        <span class="min-w-0 flex-1 truncate font-semibold md:hidden">{{ last($crumbs) ?: config('app.name') }}</span>

        <button type="button" class="group hidden h-10 w-72 items-center gap-2 rounded-lg border border-border bg-card/70 px-3 text-left text-sm text-muted transition hover:border-input hover:bg-card hover:shadow-soft xl:flex" data-search-open>
            {{ icon('search', 'size-4 group-hover:text-primary') }}<span class="flex-1">ຄົ້ນຫາສະມາຊິກ, ກິດຈະກຳ, ໜ້າ...</span>
            <kbd class="rounded-md border border-border bg-surface-2 px-1.5 font-sans text-xs">Ctrl K</kbd>
        </button>
        <button type="button" class="icon-btn xl:hidden" data-search-open aria-label="ຄົ້ນຫາ (Ctrl+K)">{{ icon('search') }}</button>

        {{-- Theme: light / dark / follow the device --}}
        <div class="relative">
            <button type="button" class="icon-btn theme-trigger" data-toggle="theme-menu" aria-haspopup="menu" aria-expanded="false" aria-controls="theme-menu" aria-label="ຮູບແບບສີ">
                <span class="dark:hidden">{{ icon('sun') }}</span><span class="hidden dark:inline">{{ icon('moon') }}</span>
            </button>
            <div id="theme-menu" data-dropdown class="menu-pop absolute right-0 z-40 mt-2 hidden w-52 rounded-xl border border-border bg-card p-1.5 shadow-pop" role="menu" aria-label="ຮູບແບບສີ">
                @foreach ($themes as $value => [$ic, $label])
                    <button type="button" class="menu-item" role="menuitemradio" data-theme-option="{{ $value }}" aria-checked="false">
                        {{ icon($ic, 'size-4') }}<span class="flex-1">{{ $label }}</span>
                        <span class="menu-check text-primary">{{ icon('check', 'size-4') }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        @auth
            <div class="relative">
                <button type="button" class="icon-btn" id="notif-button" aria-haspopup="dialog" aria-expanded="false" aria-controls="notif-panel" aria-label="ແຈ້ງເຕືອນ">
                    {{ icon('bell') }}
                    <span id="notif-count" class="absolute -top-1.5 -right-1.5 hidden min-w-5 rounded-full bg-destructive px-1 text-center text-[0.6875rem] leading-5 font-bold text-white ring-2 ring-card"></span>
                </button>
                @include('partials.notifications')
            </div>
            <div class="relative hidden sm:block">
                <button type="button" class="flex h-11 items-center gap-2 rounded-full border border-border bg-card py-1 pr-3 pl-1 transition hover:border-input hover:shadow-soft active:scale-[0.98]" data-toggle="user-menu" aria-expanded="false" aria-haspopup="menu" aria-controls="user-menu">
                    <x-avatar :name="$user->display_name" :url="$user->avatarUrl()" size="sm" class="!bg-primary !text-primary-foreground" />
                    <span class="max-w-32 truncate text-sm font-semibold">{{ $user->display_name }}</span>
                    {{ icon('chevron-down', 'size-4 text-muted') }}
                </button>
                <div id="user-menu" data-dropdown class="menu-pop absolute right-0 z-40 mt-2 hidden w-64 rounded-xl border border-border bg-card p-1.5 shadow-pop" role="menu" aria-label="ບັນຊີ">
                    <div class="flex items-center gap-3 px-3 pt-2 pb-3">
                        <x-avatar :name="$user->display_name" :url="$user->avatarUrl()" size="md" class="!bg-primary !text-primary-foreground" />
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $user->display_name }}</p>
                            <p class="truncate text-xs text-muted">{{ $user->email }}</p>
                            <x-badge tone="primary" class="mt-1 text-[0.6875rem]">{{ $user->roleLabel() }}</x-badge>
                        </div>
                    </div>
                    <div class="my-1 h-px bg-border"></div>
                    <a href="{{ route('profile') }}" class="menu-item" role="menuitem">{{ icon('user-round', 'size-4') }} ໂປຣໄຟລ໌ຂອງຂ້ອຍ</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="menu-item text-destructive hover:!bg-destructive-bg [&_svg]:!text-destructive" role="menuitem">{{ icon('log-out', 'size-4') }} ອອກຈາກລະບົບ</button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login', ['redirect' => '/'.ltrim(request()->path(), '/')]) }}" class="btn-primary btn-sm hidden sm:inline-flex">{{ icon('user-round', 'size-4') }} ເຂົ້າສູ່ລະບົບ</a>
        @endauth
    </header>

    <main id="main" class="page-enter mx-auto max-w-[90rem] px-4 pt-6 pb-28 sm:px-6 sm:pt-8 lg:px-8 lg:pb-12">
        <x-pending-banner />
        {{ $slot }}
    </main>
</div>

{{-- Mobile bottom navigation (below lg) --}}
<nav class="glass no-print fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-border/70 pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="ເມນູລຸ່ມ">
    @foreach (\App\View\Navigation::BOTTOM as [$route, $ic, $label, $pattern])
        @php $isCurrent = request()->routeIs($pattern) && ! ($route === 'members.index' && request('view') === 'tree'); @endphp
        <a href="{{ route($route) }}" class="bottom-link" @if ($isCurrent) aria-current="page" @endif>
            <span class="pill">{{ icon($ic, 'size-5') }}</span>{{ $label }}
        </a>
    @endforeach
    <button type="button" class="bottom-link" data-drawer-open aria-controls="sidebar"><span class="pill">{{ icon('menu', 'size-5') }}</span>ເມນູ</button>
</nav>

<div id="tooltip" class="tooltip" role="tooltip" aria-hidden="true"></div>
@include('partials.search')
<x-confirm-dialog />
<x-flash />
{{ $after ?? '' }}
</body>
</html>
