<a class="navbar-brand {{ request()->routeIs('home') ? 'lg:hidden' : '' }}"
   href="{{ route('home') }}">
    @if(request()->cookie('logo') === 'retro')
        <img class="hidden md:inline-block" src="{{ asset('assets/images/ra-logo-sm.webp') }}" alt="{{ config('app.name') }}">
        <img class="inline-block md:hidden" src="{{ asset('assets/images/ra-icon.webp') }}" alt="{{ config('app.name') }}">
    @else
        <img class="h-6" src="{{ asset('assets/images/ra-icon.webp') }}" alt="{{ config('app.name') }}">
    @endif
</a>
