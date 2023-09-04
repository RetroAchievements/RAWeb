<x-section class="brand-top">
    <x-container>
        {{--<div class="hidden lg:flex gap-4 justify-between items-center">--}}
        <div class="lg:grid lg:grid-cols-[1fr_340px] xl:grid-cols-[1fr_380px] gap-4 items-center">
            {{-- TODO re-build settings page for logo --}}
            {{--@if(request()->cookie('logo') === 'retro')
                <div class="logo-container lg:flex justify-start items-center pr-4 pt-4">
                    <div class="text-center">
                        <a href="{{ route('home') }}">
                            <img class="logo-retro {{ request()->routeIs('home') ? 'logo-retro-lg mt-1' : '' }} img-fluid ml-2"
                                 src="{{ asset('assets/images/ra-logo-sm.webp') }}"
                                 alt="{{ config('app.name') }}">
                        </a>
                    </div>
                </div>
            @else
                <div class="logo-container flex justify-center pr-4 pt-4">
                    <a href="{{ route('home') }}">
                        <div class="flex flex-row">
                            <img class="logo {{ request()->routeIs('home') ? 'logo-retro-lg' : '' }}"
                                 src="{{ asset('assets/images/ra-icon.webp') }}"
                                 alt="{{ config('app.name') }}">
                            <div class="hidden md:flex flex-col justify-end ml-2">
                                <h1 class="mb-1 lh-1">{{ config('app.name') }}</h1>
                                <div class="ml-1 lh-1 description">
                                    {{ __('app.description') }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endif--}}
            <div class="p-4 text-center xl:text-left">
                <a href="{{ route('home') }}">
                    <img class="max-w-[550px] w-full xl:w-auto xl:max-h-[80px]" fetchpriority="high" src="{{ asset('assets/images/ra-logo-sm.webp') }}" alt="RetroAchievements logo">
                </a>
            </div>
            <x-user.top-card/>
        </div>
    </x-container>
</x-section>
