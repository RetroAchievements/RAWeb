<!doctype html prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-head
    :page-title="$pageTitle ?? null"
    :page-description="$pageDescription ?? null"
    :page-image="$pageImage ?? null"
    :page-type="$pageType ?? null"
    :permalink="$permalink ?? null"
    :canonical-url="$permalink ?? null"
/>
<body
    {{ ($wide ?? false) ? 'wide' : '' }}
    data-scheme="{{ request()->cookie('scheme', '') }}"
    data-theme="{{ request()->cookie('theme', '') }}"
    class="{{ config('app.debug') ? 'debug' : '' }} {{ !Route::is('news.index') ? 'with-news' : '' }} with-footer"
>
{{--@if(Route::is('home'))--}}
<x-brand-top/>
{{--@endif--}}
<x-navbar class="lg:h-14 bg-body flex flex-col w-full justify-center lg:sticky lg:top-0">
    {{--<x-slot name="brand">
        <x-menu.brand />
    </x-slot>--}}
    <x-menu.main/>
    {{--<x-slot name="right">
        <div class="ml-auto"></div>
        @if(!Route::is('search'))
            <ul class="navbar-nav lg:hidden">
                <x-nav-item :link="route('search')">
                    <x-fas-search/>
                </x-nav-item>
            </ul>
            <div class="hidden lg:block">
                <livewire:supersearch dropdown/>
            </div>
        @endif
        <x-menu.notifications/>
        @can('accessManagementTools')
            <x-menu.management/>
        @endcan
        <x-menu.account/>
    </x-slot>--}}
    {{--<x-slot name="mobile">
        <x-menu.main :mobile="true" />
    </x-slot>--}}
</x-navbar>
<x-messages/>
<x-content>
    <x-slot name="header">
        {{ $header ?? '' }}
    </x-slot>
    <x-slot name="breadcrumb">
        {{ $breadcrumb ?? '' }}
    </x-slot>
    @if ($bufferedOutput ?? false)
        <x-container>
            <main class="mb-5" data-scroll-target>
                {!! $bufferedOutput ?? '' !!}
            </main>
        </x-container>
    @else
        <x-main :sidebarPosition="$sidebarPosition ?? 'right'">
            <x-slot name="sidebar">
                {{ $sidebar ?? '' }}
            </x-slot>
            {{ $slot ?? '' }}
        </x-main>
    @endif
</x-content>
<footer>
    {{--@if(!Route::is('news.index'))
        <livewire:news-teaser />
    @endif--}}
    <x-footer-navigation/>
</footer>
<x-body-end/>
</body>
</html>
