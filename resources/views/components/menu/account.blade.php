{{--@guest
    <ul class="navbar-nav">
        @if($settings->get('auth.registration', true))
            <li class="nav-item hidden lg:inline-block">
                <a class="nav-link" href="{{ route('register') }}">
                    <span class="sr-only">{{ __('Register') }}</span>
                    <span class="hidden md:inline-block">{{ __('Register') }}</span>
                </a>
            </li>
        @endif
        <li class="nav-item">
            <a class="nav-link" href="{{ route('login') }}">
                <x-fas-power-off />
                <span class="sr-only">{{ __('Sign In') }}</span>
                <span class="hidden lg:inline-block">{{ __('Sign In') }}</span>
            </a>
        </li>
    </ul>
@endguest--}}
@auth
    <ul class="navbar-nav">
        {{--
        Note: keep icon avatar in its own navbar-nav
        otherwise it jumps around vertically with other text nav items in the same group
        --}}
        <x-nav-dropdown trigger-class="py-0" dropdown-class="dropdown-menu-right">
            <x-slot name="trigger">
                <x-user.avatar :user="request()->user()" display="icon" iconSize="sm" :link="false" :tooltip="false" />
            </x-slot>
            {{--<x-dropdown-header>{{ request()->user()->username }}</x-dropdown-header>--}}
            {{--<div class="dropdown-divider"></div>--}}
            {{--<x-dropdown-item :link="route('user.show', request()->user())">{{ __res('profile', 1) }}</x-dropdown-item>--}}
            {{--<x-dropdown-item :link="route('friend.index')">{{ __res('friend') }}</x-dropdown-item>--}}
            {{--<x-dropdown-item :link="route('inbox')">{{ __('Inbox') }}</x-dropdown-item>--}}
            {{--<a class="dropdown-item" href="{{ route('history.index') }}">History</a>--}}
            {{--<div class="dropdown-divider"></div>--}}
            {{--<x-dropdown-item :link="route('settings')">{{ __res('setting') }}</x-dropdown-item>--}}
            {{--<x-form :action="route('logout')">
                <button class="dropdown-item">{{ __('Sign Out') }}</button>
            </x-form>--}}
        </x-nav-dropdown>
    </ul>
    {{--<ul class="navbar-nav">
        <li class="nav-item hidden lg:inline-block"><span class="nav-link">{{ request()->user()->points_total }}</span></li>
    </ul>--}}
@endauth
{{--<li wire:offline>
    Offline
</li>--}}
