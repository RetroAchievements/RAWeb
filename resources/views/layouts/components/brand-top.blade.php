<x-section class="brand-top">
    <x-container>
        <div class="hidden lg:flex justify-lg:content-between">
            @if(request()->cookie('logo') === 'retro')
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
                            <div class="hidden md:flex flex-column justify-end ml-2">
                                <h1 class="mb-1 lh-1">{{ config('app.name') }}</h1>
                                <div class="ml-1 lh-1 description">
                                    {{ __('app.description') }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endif
        </div>
    </x-container>
</x-section>
