<x-section class="brand-top bg-embedded">
    <x-container>
        <div class="hidden lg:block">
            @if(request()->cookie('logo') === 'retro')
                <div class="logo-container py-4">
                    <div class="text-center">
                        <a href="{{ route('home') }}">
                            <img class="logo-retro w-full"
                                 fetchpriority="high"
                                 src="{{ asset('assets/images/ra-logo-sm.webp') }}"
                                 alt="{{ config('app.name') }}"
                            >
                        </a>
                    </div>
                </div>
            @else
                <div class="logo-container flex justify-start pt-6 pb-4 pl-4">
                    <a href="{{ route('home') }}" class="text-inverse">
                        <div class="flex flex-row">
                            <img class="logo"
                                 fetchpriority="high"
                                 src="{{ asset('assets/images/ra-icon.webp') }}"
                                 alt="{{ config('app.name') }}"
                            >
                            <div class="hidden md:flex flex-col justify-end ml-2">
                                <h1 class="mb-1">{{ config('app.name') }}</h1>
                                <div class="ml-1 description">
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
