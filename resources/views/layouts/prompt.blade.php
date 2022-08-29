<!doctype html data-theme="{{ request()->cookie('theme', '') }}">
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-head/>
<body
    data-scheme="{{ request()->cookie('scheme', '') }}"
    data-theme="{{ request()->cookie('theme', '') }}"
    class="{{ config('app.debug') ? 'debug' : '' }} flex flex-col justify-center"
>
<div>
    <x-messages/>
    @if($withTop)
        <x-container>
            <x-section class="brand-top bg-transparent mb-0">
                @if(request()->cookie('logo') === 'retro')
                    <div class="logo-container py-4 lg:flex flex-row justify-center items-center">
                        <div class="text-center">
                            <a href="{{ route('home') }}">
                                <img class="logo-retro {{ request()->routeIs('home') ? 'logo-retro-lg mt-1' : '' }} img-fluid ml-2"
                                     src="{{ asset('assets/images/ra-logo-sm.webp') }}"
                                     alt="{{ config('app.name') }}">
                            </a>
                        </div>
                    </div>
                @else
                    <div class="logo-container px-3 py-4 flex flex-col justify-center">
                        <a href="{{ route('home') }}">
                            <div class="flex flex-row justify-center">
                                <img class="logo {{ request()->routeIs('home') ? 'logo-retro-lg' : '' }}"
                                     src="{{ asset('assets/images/ra-icon.webp') }}"
                                     alt="{{ config('app.name') }}">
                            </div>
                        </a>
                    </div>
                @endif
            </x-section>
        </x-container>
    @endif
    <x-header class="mb-5 text-center">
        {{ $header ?? '' }}
    </x-header>
    <x-main>
        {{ $slot }}
    </x-main>
</div>
<x-body-end/>
</body>
</html>
