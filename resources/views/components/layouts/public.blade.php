{{-- PublicLayout: Login, 404, Unauthorized --}}
@props(['title' => null, 'bare' => false])
<!DOCTYPE html>
<html lang="lo" data-theme-pref="{{ auth()->user()?->theme ?? 'system' }}" data-font-scale="{{ auth()->user()?->font_scale ?? 100 }}" @guest data-guest @endguest>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="view-transition" content="same-origin">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <script src="{{ asset('js/theme-init.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh">
    {{ icon_sprite(\App\View\Navigation::SPRITE) }}
    <div id="page-progress" aria-hidden="true"></div>
    @unless ($bare)
        <header class="glass flex h-[4.25rem] items-center border-b border-border/80 px-6 sm:px-8">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <x-logo class="size-9" />
                <span class="font-bold">Kwv Tij Hmoob Xyooj</span>
            </a>
        </header>
    @endunless
    <main id="main" class="page-enter">{{ $slot }}</main>
    <div id="tooltip" class="tooltip" role="tooltip" aria-hidden="true"></div>
    <x-flash />
</body>
</html>
