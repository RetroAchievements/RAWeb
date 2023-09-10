<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-head
    :page-title="$pageTitle"
    :page-description="$pageDescription"
    :page-image="$pageImage"
    :page-type="$pageType"
    :permalink="$permalink"
    :canonical-url="$permalink"
/>
<body
    data-scheme="{{ request()->cookie('scheme', '') }}"
    data-theme="{{ request()->cookie('theme', '') }}"
    class="{{ config('app.debug') ? 'debug' : '' }} {{ !Route::is('news.index') ? 'with-news' : '' }} with-footer"
>
<x-navbar class="flex flex-col w-full justify-center lg:sticky lg:top-0">
    <x-slot name="brand">
        <x-menu.brand />
    </x-slot>
    <x-menu.main />
    <x-slot name="right">
        <div class="ml-auto"></div>
        <x-menu.search/>
        @can('accessManagementTools')
            <x-menu.management/>
        @endcan
        <x-menu.notifications/>
        <x-menu.account/>
    </x-slot>
    <x-slot name="mobile">
        <x-menu.main :mobile="true"/>
    </x-slot>
</x-navbar>
<x-content>
    <x-slot name="header">
        {{ $header ?? '' }}
    </x-slot>
    <x-slot name="breadcrumb">
        {{ $breadcrumb ?? '' }}
    </x-slot>
    @if(!empty($breadcrumb))
        <x-slot name="breadcrumb">
            {{ $breadcrumb }}
        </x-slot>
    @endif
    <x-main :sidebarPosition="$sidebarPosition">
        <x-slot name="sidebar">
            {{ $sidebar ?? '' }}
        </x-slot>
        {{ $slot ?? '' }}
        {{--            {!! $main ?? '' !!}--}}
    </x-main>
</x-content>
<footer>
    {{--@if(!Route::is('news.index'))
        <livewire:news-teaser />
    @endif--}}
    <x-footer-navigation />
</footer>
<x-body-end/>
</body>
</html>
