<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-head
    :page="$page ?? null"
    :page-title="$pageTitle ?? null"
    :page-description="$pageDescription ?? null"
    :page-image="$pageImage ?? null"
    :page-type="$pageType ?? null"
    :permalink="$permalink ?? null"
    :canonical-url="$permalink ?? null"
    :noindex="$noindex ?? null"
/>
<body
    data-scheme="{{ request()->cookie('scheme', '') }}"
    data-theme="{{ request()->cookie('theme', '') }}"
    class="{{ config('app.debug') ? 'debug' : '' }} {{ !Route::is('news.index') ? 'with-news' : '' }} with-footer"
>
    @if (Route::is('home'))
        <x-brand-top />
    @endif

    <x-navbar class="flex flex-col w-full justify-center lg:sticky lg:top-0">
        <x-slot name="brand">
            <x-menu.brand />
        </x-slot>

        <x-menu.main />
        
        <x-slot name="right">
            <div class="ml-auto"></div>
            <x-menu.search />
            @can('accessManagementTools')
                <x-menu.management class="hidden lg:inline-block"/>
            @endcan
            <x-menu.notifications class="hidden lg:inline-block" />
            <x-menu.account />
        </x-slot>
        
        <x-slot name="mobile">
            <x-menu.main :mobile="true" />
            <div class="ml-auto"></div>
            @can('accessManagementTools')
                <x-menu.management />
            @endcan
            <x-menu.notifications />
        </x-slot>
    </x-navbar>


    <x-content>
        @if (!empty($page))
            @inertia
        @else
            <x-slot name="header">
                {{ $header ?? '' }}
            </x-slot>

            <x-slot name="breadcrumb">
                {{ $breadcrumb ?? '' }}
            </x-slot>

            <x-main :sidebarPosition="$sidebarPosition ?? 'right'">
                @if (!empty($page))
                    @inertia
                @endif
    
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
        <x-footer-navigation />
    </footer>

    <x-body-end />
</body>
</html>
