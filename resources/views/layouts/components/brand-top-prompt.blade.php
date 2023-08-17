<x-container>
    <x-section class="brand-top mb-2">
        @if(request()->cookie('logo') === 'retro')
            <div class="logo-container px-3 py-4 text-center">
                <div class="text-center">
                    <a href="{{ route('home') }}">
                        <img class="logo-retro {{ request()->routeIs('home') ? 'logo-retro-lg mt-1' : '' }} w-full ml-2"
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
