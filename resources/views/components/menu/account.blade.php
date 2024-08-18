<?php

use App\Models\User;
use App\Enums\Permissions;

/** @var ?User $user */
$user = request()->user();
?>
@guest
    <div class="nav-item">
        <a class="nav-link" href="{{ route('login') }}">
            <x-fas-power-off class="mr-1" />
            <span class="sr-only">{{ __('Sign in') }}</span>
            <span class="hidden lg:inline-block">{{ __('Sign in') }}</span>
        </a>
    </div>
    @if($settings->get('auth.registration', true))
        <div class="nav-item">
            {{--<a class="nav-link" href="{{ route('register') }}">--}}
            <a class="nav-link nav-link-themed" href="{{ url('createaccount.php') }}">
                <x-fas-user-plus class="mr-1 link-color" />
                <span class="sr-only">{{ __('Sign up') }}</span>
                <span class="hidden lg:inline-block link-color">{{ __('Sign up') }}</span>
            </a>
        </div>
    @endif
@endguest
@auth
    <div class="nav-link flex-col justify-center items-end text-2xs" style="line-height: 1.1em">
        @if($user->points_softcore && $user->points_softcore > $user->points)
            <div class='softcore cursor-help' title="Points earned in softcore mode">{{ localized_number($user->RASoftcorePoints) }}</div>
        @endif

        @if($user->points)
            <div class="text-color cursor-help" title="Points earned in hardcore mode">{{ localized_number($user->points) }}</div>
        @endif

        @if($user->points_weighted)
            <x-points-weighted-container>
                <span class='trueratio'>{{ localized_number($user->points_weighted) }}</span>
            </x-points-weighted-container>
        @endif

        @if($user->points_softcore && $user->points_softcore <= $user->points)
            <div class='softcore cursor-help' title="Points earned in softcore mode">{{ localized_number($user->RASoftcorePoints) }}</div>
        @endif
    </div>
    <x-nav-dropdown trigger-class="py-0" dropdown-class="dropdown-menu-right" :desktopHref="route('user.show', $user)">
        <x-slot name="trigger">
            <x-user.avatar :user="$user" display="icon" :hasHref="false" iconSize="sm" :tooltip="false" class="rounded-sm userpic" />
        </x-slot>
        <x-dropdown-header>{{ $user->username }}</x-dropdown-header>
        <x-dropdown-item :href="route('user.show', $user)">{{ __res('profile', 1) }}</x-dropdown-item>
        <x-dropdown-item :href="route('user.completion-progress', ['user' => $user])">Completion Progress</x-dropdown-item>

        @if($user->Permissions >= Permissions::Registered)
            <x-dropdown-item :href="url('gameList.php?t=play')">Want to Play Games</x-dropdown-item>
            <x-dropdown-item :href="route('games.suggest')">Game Suggestions</x-dropdown-item>
        @endif
        @if($user->ContribCount > 0 || $user->Permissions >= Permissions::JuniorDeveloper)
            <div class="dropdown-divider"></div>
            @if($user->ContribCount > 0)
                <x-dropdown-item :href="url('individualdevstats.php?u=' . $user->username)">Developer Profile</x-dropdown-item>
            @endif
            @if($user->Permissions >= Permissions::Developer)
                <x-dropdown-item :href="url('gameList.php?t=develop&f=2')">Want to Develop Games</x-dropdown-item>
            @endif
            @if($user->ContribCount > 0)
                <x-dropdown-item :href="route('developer.feed', ['user' => $user])">Feed</x-dropdown-item>
            @endif
            @if($user->ContribCount > 0)
                <x-dropdown-item :href="route('developer.tickets', ['user' => $user])">Tickets</x-dropdown-item>
                <x-dropdown-item :href="route('developer.sets', ['user' => $user])">Sets</x-dropdown-item>
            @endif
            @if($user->Permissions >= Permissions::JuniorDeveloper)
                <x-dropdown-item :href="route('developer.claims', ['user' => $user])">Claims</x-dropdown-item>
            @endif
        @endif
        <div class="dropdown-divider"></div>
        <x-dropdown-item :href="url('achievementList.php?s=19&p=1')">Unlocked Achievements</x-dropdown-item>
        <x-dropdown-item :href="url('setRequestList.php?u=' . $user->username)">Requested Sets</x-dropdown-item>
        {{--<a class="dropdown-item" href="{{ route('history.index') }}">History</a>--}}
        <x-dropdown-item :href="url('history.php')">History</x-dropdown-item>
        {{--<x-dropdown-item :href="route('follower.index')">{{ __res('follower') }}</x-dropdown-item>--}}
        <x-dropdown-item :href="url('friends.php')">Following</x-dropdown-item>
        <x-dropdown-item :href="route('message-thread.index')">{{ __res('message') }}</x-dropdown-item>
        <div class="dropdown-divider"></div>
        <x-dropdown-item :href="url('reorderSiteAwards.php')">Reorder Site Awards</x-dropdown-item>
        {{--<x-dropdown-item :href="route('settings')">{{ __res('setting') }}</x-dropdown-item>--}}
        <x-dropdown-item :href="route('settings.show')">Settings</x-dropdown-item>
        <div class="dropdown-divider"></div>
        {{--<x-base.form :action="route('logout')">--}}
        <x-base.form :action="route('logout')">
            <button class="dropdown-item">{{ __('Sign out') }}</button>
        </x-base.form>
    </x-nav-dropdown>
@endauth
