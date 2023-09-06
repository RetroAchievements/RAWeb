<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ (!empty($pageTitle) ? $pageTitle . ' · ' : '') . config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset(app()->environment('local', 'stage') ? 'assets/images/favicon-gray.webp' : 'assets/images/favicon.webp') }}">
    <link rel="preload" as="image" importance="high" href="{{ asset('assets/images/ra-icon.webp') }}">
    <link rel="image_src" href="{{ asset('assets/images/ra-icon.webp') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="copyright" content="Copyright 2014-{{ date('Y') }}">
    <meta name="description" content="{{ $pageDescription ?? __('app.description') }}">
    <meta name="keywords" content="games, achievements, retro, emulator">
    <meta name="format-detection" content="telephone=no">
    @if(config('services.facebook.client_id'))
        <meta property="fb:app_id" content="{{ config('services.facebook.client_id') }}">
    @endif
    <meta property="og:title" content="{{ $pageTitle ?? config('app.name') }}">
    <meta property="og:description" content="{{ $pageDescription ?? $pageTitle ?? __('app.description') }}">
    <meta property="og:image" content="{{ $pageImage ?? asset('assets/images/favicon.webp') }}">
    <meta property="og:url" content="{{ $permalink ?? request()->url() }}">
    <meta property="og:type" content="{{ $pageType ?? 'website' }}">
    <meta name="theme-color" content="#2C2E30">
    <link rel="canonical" href="{{ $canonicalUrl ?? request()->url() }}">
    <link rel="preconnect" href="{{ config('filesystems.disks.media.url') }}">
    <link rel="dns-prefetch" href="{{ config('filesystems.disks.media.url') }}">
    <link rel="preconnect" href="{{ config('filesystems.disks.static.url') }}">
    <link rel="dns-prefetch" href="{{ config('filesystems.disks.static.url') }}">

    {{-- TODO replace with ESM imports, Alpine, tailwind --}}
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/themes/sunny/jquery-ui.css">

    @vite(['resources/js/app.ts', 'resources/css/app.css'], config('vite.build_path'))

    {{-- TODO add livewire--}}
    {{--<livewire:styles />--}}

    {{-- BEGIN v1 compat --}}
    <script>window.assetUrl = "{{ config('app.asset_url') }}";</script>
    <script>window.mediaAssetUrl = "{{ config('app.media_url') }}";</script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.23/jquery-ui.min.js"></script>

    {{-- TODO replace with ESM imports, Alpine, tailwind --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/knockout/3.5.0/knockout-min.js"></script>

    @if(app()->environment('local'))
        <script src="/js/all.js?v={{ random_int(0, mt_getrandmax()) }}"></script>
    @else
        <script src="/js/all-{{ config('app.version') }}.js"></script>
    @endif
    {{-- END v1 compat --}}

    @stack('head')
</head>
