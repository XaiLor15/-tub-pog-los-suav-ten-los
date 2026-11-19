{{-- AppLayout: Sidebar + Header (search, notifications, theme, user menu) + mobile bottom nav. --}}
@props(['title' => null, 'crumbs' => []])
@php
    $user = auth()->user();
    $groups = \App\View\Navigation::groups($user);
@endphp
<!DOCTYPE html>
<html lang="lo" data-theme-pref="{{ $user?->theme ?? 'system' }}" data-font-scale="{{ $user?->font_scale ?? 100 }}" @guest data-guest @endguest>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script src="{{ asset('js/theme-init.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh" data-auth="{{ $user ? $user->role : 'guest' }}">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-card focus:px-4 focus:py-2">ໄປຫາເນື້ອຫາ</a>

{{-- Sidebar (desktop fixed / mobile drawer) --}}
<div id="drawer-backdrop" class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden" data-drawer-close></div>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-border bg-card transition-transform duration-200 lg:translate-x-0" aria-label="ເມນູຫຼັກ">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-5 pt-5 pb-4">
        <x-logo class="size-10" />
        <span class="leading-tight">
            <span class="block text-[0.9375rem] font-bold text-foreground">Kwv Tij Hmoob Xyooj</span>
            <span class="block text-xs text-muted">ລະບົບຄຸ້ມຄອງຕະກູນ</span>
        </span>
    </a>
    <nav class="flex-1 overflow-y-auto px-3 pb-4">
        @foreach ($groups as $group)
            <p class="nav-group">{{ $group['label'] }}</p>
            @foreach ($group['items'] as $item)
                <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="nav-item" @if (\App\View\Navigation::isActive($item)) aria-current="page" @endif>
                    {{ icon($item['icon'], 'size-5') }}
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if (! empty($item['badge']))
                        <span class="badge bg-warning-bg px-2 text-xs text-warning">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>
    <div class="border-t border-border p-3">
        @auth
            <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-xl border border-border p-3 hover:bg-surface-2">
                <x-avatar :name="$user->display_name" :url="$user->avatarUrl()" size="sm" class="!bg-primary !text-primary-foreground" />
                <span class="min-w-0 leading-tight">
                    <span class="block truncate font-semibold">{{ $user->display_name }}</span>
                    <span class="block text-xs text-muted">{{ $user->roleLabel() }}</span>
                </span>
            </a>
        @else
            <a href="{{ route('login') }}" class="btn-primary w-full">{{ icon('user', 'size-4') }} ເຂົ້າສູ່ລະບົບ</a>
        @endauth
    </div>
</aside>

<div class="lg:pl-64">
    {{-- AppHeader --}}
    <header class="no-print sticky top-0 z-30 flex h-[4.375rem] items-center gap-3 border-b border-border bg-card/95 px-4 backdrop-blur sm:px-6">
        <button type="button" class="icon-btn lg:hidden" data-drawer-open aria-label="ເປີດເມນູ" aria-controls="sidebar">{{ icon('menu') }}</button>
        <nav aria-label="Breadcrumb" class="hidden min-w-0 flex-1 text-sm md:block">
            <ol class="flex items-center gap-2 truncate">
                @foreach ($crumbs as $i => $crumb)
                    @if ($i > 0)<li class="text-muted" aria-hidden="true">/</li>@endif
                    <li class="{{ $loop->last ? 'font-semibold text-foreground' : 'text-muted' }} truncate" @if ($loop->last) aria-current="page" @endif>{{ $crumb }}</li>
                @endforeach
            </ol>
        </nav>
        <span class="flex-1 truncate font-semibold md:hidden">{{ last($crumbs) ?: config('app.name') }}</span>

        <button type="button" class="hidden h-11 w-72 items-center gap-2 rounded-lg border border-border bg-background px-3 text-left text-sm text-muted hover:border-input xl:flex" data-search-open>
            {{ icon('search', 'size-4') }}<span class="flex-1">ຄົ້ນຫາສະມາຊິກ, ກິດຈະກຳ, ໜ້າ...</span>
            <kbd class="rounded border border-border bg-card px-1.5 text-xs">Ctrl K</kbd>
        </button>
        <button type="button" class="icon-btn xl:hidden" data-search-open aria-label="ຄົ້ນຫາ (Ctrl+K)">{{ icon('search') }}</button>
        <button type="button" class="icon-btn hidden sm:inline-flex" data-theme-toggle aria-label="ປ່ຽນໂໝດສີ">
            <span class="dark:hidden">{{ icon('moon') }}</span><span class="hidden dark:inline">{{ icon('sun') }}</span>
        </button>

        @auth
            <div class="relative">
                <button type="button" class="icon-btn relative" id="notif-button" aria-haspopup="dialog" aria-expanded="false" aria-controls="notif-panel" aria-label="ແຈ້ງເຕືອນ">
                    {{ icon('bell') }}
                    <span id="notif-count" class="absolute -top-1.5 -right-1.5 hidden min-w-5 rounded-full bg-destructive px-1 text-center text-[0.6875rem] leading-5 font-bold text-white"></span>
                </button>
                @include('partials.notifications')
            </div>
            <div class="relative hidden sm:block">
                <button type="button" class="flex h-11 items-center gap-2 rounded-full border border-border bg-card py-1 pr-3 pl-1 hover:bg-surface-2" data-toggle="user-menu" aria-expanded="false" aria-haspopup="menu">
                    <x-avatar :name="$user->display_name" :url="$user->avatarUrl()" size="sm" class="!bg-primary !text-primary-foreground" />
                    <span class="max-w-32 truncate text-sm font-semibold">{{ $user->display_name }}</span>
                    {{ icon('chevron-down', 'size-4 text-muted') }}
                </button>
                <div id="user-menu" data-dropdown class="absolute right-0 z-40 mt-2 hidden w-56 rounded-xl border border-border bg-card p-1.5 shadow-pop" role="menu">
                    <a href="{{ route('profile') }}" class="nav-item" role="menuitem">{{ icon('user', 'size-4') }} ໂປຣໄຟລ໌ຂອງຂ້ອຍ</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="nav-item w-full text-destructive" role="menuitem">{{ icon('log-out', 'size-4') }} ອອກຈາກລະບົບ</button>
                    </form>
                </div>
            </div>
        @else
            <a href="{{ route('login', ['redirect' => '/'.ltrim(request()->path(), '/')]) }}" class="btn-primary btn-sm hidden sm:inline-flex">ເຂົ້າສູ່ລະບົບ</a>
        @endauth
    </header>

    <main id="main" class="mx-auto max-w-[90rem] px-4 pt-6 pb-28 sm:px-8 sm:pt-8 lg:pb-12">
        <x-pending-banner />
        {{ $slot }}
    </main>
</div>

{{-- Mobile bottom navigation (< 640px design; shown below lg) --}}
<nav class="no-print fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-border bg-card pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="ເມນູລຸ່ມ">
    @foreach ([['dashboard', 'layout-grid', 'ໜ້າຫຼັກ', 'dashboard'], ['members.index', 'users', 'ສະມາຊິກ', 'members.*'], ['activities.index', 'calendar', 'ກິດຈະກຳ', 'activities.*'], ['news.index', 'newspaper', 'ຂ່າວ', 'news.*']] as [$r, $ic, $lbl, $act])
        <a href="{{ route($r) }}" class="flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs font-medium {{ request()->routeIs($act) ? 'text-primary' : 'text-muted' }}" @if (request()->routeIs($act)) aria-current="page" @endif>
            {{ icon($ic, 'size-5') }}{{ $lbl }}
        </a>
    @endforeach
    <button type="button" class="flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs font-medium text-muted" data-drawer-open aria-controls="sidebar">{{ icon('menu', 'size-5') }}ເມນູ</button>
</nav>

@include('partials.search')
<x-confirm-dialog />
<x-flash />
{{ $after ?? '' }}
</body>
</html>
