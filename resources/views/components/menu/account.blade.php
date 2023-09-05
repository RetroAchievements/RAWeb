<?php

use App\Site\Models\User;
use App\Site\Enums\Permissions;

/** @var ?User $user */
$user = request()->user();
?>
@guest
    <ul class="navbar-nav">
        @if($settings->get('auth.registration', true))
            <li class="nav-item hidden lg:inline-block">
                {{--<a class="nav-link" href="{{ route('register') }}">--}}
                <a class="nav-link" href="{{ url('createaccount.php') }}">
                    <span class="sr-only">{{ __('Register') }}</span>
                    <span class="hidden md:inline-block">{{ __('Register') }}</span>
                </a>
            </li>
        @endif
        {{--<li class="nav-item">
            <a class="nav-link" href="{{ route('login') }}">
                <x-fas-power-off />
                <span class="sr-only">{{ __('Sign In') }}</span>
                <span class="hidden lg:inline-block">{{ __('Sign In') }}</span>
            </a>
        </li>--}}
    </ul>
@endguest
@auth
    <div class="nav-link flex-col justify-center items-end text-2xs" style="line-height: 1.1em">
        @if($user->points_total)
            <div class="text-color cursor-help" title="Points earned in hardcore mode">{{ localized_number($user->points_total) }}</div>
        @endif
        @if($user->points_weighted_total)
            <x-points-weighted-container>
                <span class='trueratio'>{{ localized_number($user->points_weighted_total) }}</span>
            </x-points-weighted-container>
        @endif
        @if($user->RASoftcorePoints)
            <div class='softcore cursor-help' title="Points earned in softcore mode">{{ localized_number($user->RASoftcorePoints) }}</div>
        @endif
    </div>
    <x-nav-dropdown trigger-class="py-0" dropdown-class="dropdown-menu-right">
        <x-slot name="trigger">
            <x-user.avatar :user="$user" display="icon" iconSize="sm" :link="false" :tooltip="false" class="rounded-sm" />
        </x-slot>
        <x-dropdown-header>{{ $user->username }}</x-dropdown-header>
        <x-dropdown-item :link="route('user.show', $user)">{{ __res('profile', 1) }}</x-dropdown-item>
        @if($user->Permissions >= Permissions::Registered)
            <x-dropdown-item :link="url('gameList.php?t=play')">Want to Play Games</x-dropdown-item>
        @endif
        @if($user->ContribCount > 0 || $user->Permissions >= Permissions::JuniorDeveloper)
            <div class="dropdown-divider"></div>
            @if($user->ContribCount > 0)
                <x-dropdown-item :link="url('individualdevstats.php?u=' . $user->username)">Developer Profile</x-dropdown-item>
                <x-dropdown-item :link="url('ticketmanager.php?u=' . $user->username)">Tickets</x-dropdown-item>
                <x-dropdown-item :link="url('gameList.php?d=' . $user->username)">Sets</x-dropdown-item>
            @endif
            @if($user->Permissions >= Permissions::JuniorDeveloper)
                <x-dropdown-item :link="url('claimlist.php?u=' . $user->username)">Claims</x-dropdown-item>
            @endif
        @endif
        <div class="dropdown-divider"></div>
        <x-dropdown-item :link="url('achievementList.php?s=19&p=1')">Unlocked Achievements</x-dropdown-item>
        <x-dropdown-item :link="url('setRequestList.php?u=' . $user->username)">Requested Sets</x-dropdown-item>
        {{--<a class="dropdown-item" href="{{ route('history.index') }}">History</a>--}}
        <x-dropdown-item :link="url('history.php')">History</x-dropdown-item>
        {{--<x-dropdown-item :link="route('follower.index')">{{ __res('follower') }}</x-dropdown-item>--}}
        <x-dropdown-item :link="url('friends.php')">Following</x-dropdown-item>
        {{--<x-dropdown-item :link="route('inbox')">{{ __('Inbox') }}</x-dropdown-item>--}}
        <x-dropdown-item :link="url('inbox.php')">Messages</x-dropdown-item>
        <div class="dropdown-divider"></div>
        <x-dropdown-item :link="url('reorderSiteAwards.php')">Reorder Site Awards</x-dropdown-item>
        {{--<x-dropdown-item :link="route('settings')">{{ __res('setting') }}</x-dropdown-item>--}}
        <x-dropdown-item :link="url('controlpanel.php')">Settings</x-dropdown-item>
        <div class="dropdown-divider"></div>
        {{--<x-form :action="route('logout')">--}}
        <x-form :action="url('request/auth/logout.php')">
            <button class="dropdown-item">{{ __('Sign Out') }}</button>
        </x-form>
    </x-nav-dropdown>
@endauth
