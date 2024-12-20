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
    <div data-vaul-drawer-wrapper="">
        @if (Route::is('home'))
            <div
                id="brand-top-wrapper"
                class="{{ Route::is('home') ? 'block' : 'hidden' }}"
            >
                <x-brand-top />
            </div>
        @endif

        <x-navbar class="flex flex-col w-full justify-center bg-embedded lg:sticky lg:top-0">
            <x-slot name="brand">
                <div 
                    id="nav-brand-wrapper"
                    class="{{ Route::is('home') ? 'lg:hidden' : '' }}"
                >
                    <x-menu.brand />
                </div>
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

        <script>
            document.addEventListener('inertia:navigate', (event) => {
                const brandTopWrapper = document.getElementById('brand-top-wrapper');
                const navBrandWrapper = document.getElementById('nav-brand-wrapper');

                const isHomeRoute = event.detail.page.url === '/';

                if (brandTopWrapper) {
                    brandTopWrapper.classList.toggle('hidden', !isHomeRoute);
                }
                if (navBrandWrapper) {
                    navBrandWrapper.className = isHomeRoute ? 'lg:hidden' : '';
                }
            });
        </script>

        <x-body-end />
    </div>
</body>
</html>
