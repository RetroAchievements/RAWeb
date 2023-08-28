<?php

use App\Site\Enums\Permissions;
use App\Site\Models\User;

/** @var User $user */
$user = request()->user();
?>
<ul class="navbar-nav">
    <x-nav-dropdown dropdown-class="dropdown-menu-right">
        <x-slot name="trigger">
            <x-fas-toolbox/>
            <span class="ml-1 hidden sm:inline-block">Manage</span>
        </x-slot>
        @can('develop')
            @can('manage', App\Community\Models\TriggerTicket::class)
                <x-dropdown-header>{{ __('Development') }}</x-dropdown-header>
                {{--<x-dropdown-item :link="route('triggers.ticket.index')">{{ __res('ticket') }}</x-dropdown-item>--}}
                <x-dropdown-item :link="url('ticketmanager.php')">{{ __res('ticket') }}</x-dropdown-item>
                <x-dropdown-item :link="url('ticketmanager.php?f=1')">Most Reported Games</x-dropdown-item>
                <x-dropdown-item :link="url('achievementinspector.php')">Achievement Inspector</x-dropdown-item>
            @endcan
            @can('manage', App\Community\Models\AchievementSetClaim::class)
                <x-dropdown-item :link="url('expiringclaims.php')">Expiring Claims</x-dropdown-item>
            @endcan
            @can('manage', App\Platform\Models\GameHash::class)
                <x-dropdown-item :link="url('latesthasheslinked.php')">Latest Linked Hashes</x-dropdown-item>
            @endcan
        @endif
        @if($user->Permissions >= Permissions::Developer)
            <x-dropdown-header>{{ __('Community') }}</x-dropdown-header>
            @can('manage', App\Community\Models\News::class)
                {{--<x-dropdown-item :link="route('news.index')">{{ __res('news') }}</x-dropdown-item>--}}
                <x-dropdown-item :link="url('submitnews.php')">{{ __res('news') }}</x-dropdown-item>
            @endcan
            @can('manage', App\Community\Models\Forum::class)
                {{--<x-dropdown-item :link="route('forum-topic.verify')">Forum Verification</x-dropdown-item>--}}
                <x-dropdown-item :link="url('viewforum.php?f=0')">Forum Verification</x-dropdown-item>
            @endcan
            {{--@can('manage', App\Site\Models\Event::class)
                <h6 class="dropdown-header">Events</h6>
            @endcan--}}
            {{--@can('manage', App\Platform\Models\IntegrationRelease::class)
                <x-dropdown-header>Releases</x-dropdown-header>
                @can('manage', App\Platform\Models\Emulator::class)
                    <x-dropdown-item :link="route('emulator.index')" :active="request()->routeIs('emulator*')">Emulators</x-dropdown-item>
                @endcan
                <x-dropdown-item :link="route('integration.release.index')" :active="request()->routeIs('integration.release*')">Integration</x-dropdown-item>
            @endcan--}}
            @if($user->Permissions >= Permissions::Moderator)
                <x-dropdown-item :link="url('admin.php')">Admin Tools</x-dropdown-item>
            @endif
        @endif
        @can('root')
            <x-dropdown-header>{{ __('System') }}</x-dropdown-header>
            @can('viewHorizon')
                <a class="dropdown-item" href="{{ url('horizon') }}" target="_blank">
                    Horizon Queue
                    <x-fas-external-link-alt/>
                </a>
            @endcan
            @can('viewLogs')
                <a class="dropdown-item" href="{{ route('log-viewer.index') }}" target="_blank">
                    Logs
                    <x-fas-external-link-alt/>
                </a>
            @endcan
            {{--@can('viewRouteUsage')
                <a class="dropdown-item" href="{{ url('route-usage') }}" target="_blank">
                    Route Usage
                    <x-fas-external-link-alt/>
                </a>
            @endcan--}}
            {{--@can('viewSyncStatus')
                <a class="dropdown-item" href="{{ route('sync-status') }}">
                    Sync Status
                </a>
            @endcan--}}
            {{--@can('viewWebSocketsDashboard')
                <a class="dropdown-item" href="{{ url('websockets') }}" target="_blank">
                    Web Sockets
                    <x-fas-external-link-alt/>
                </a>
            @endcan--}}
        @endcan
    </x-nav-dropdown>
</ul>
