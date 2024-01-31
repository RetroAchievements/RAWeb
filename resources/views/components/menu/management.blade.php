<?php

use App\Enums\Permissions;
use App\Models\User;

/** @var User $user */
$user = request()->user();

$tools = $settings['tools'] ?? null;
?>
<x-nav-dropdown :class="$class ?? ''" dropdown-class="dropdown-menu-right" :title="__('Manage')">
    <x-slot name="trigger">
        <x-fas-toolbox/>
        <span class="ml-1 hidden sm:inline-block">{{ __('Manage') }}</span>
    </x-slot>
    <div class="md:flex">
        @if($tools && !empty($tools))
            <div class="dropdown-column">
                <x-dropdown-header>{{ __('System') }}</x-dropdown-header>
                @foreach ($tools as $tool)
                    @can($tool['abilities'])
                        <x-dropdown-item :link="!empty($tool['route']) ? route($tool['route']) : url($tool['url'])">{{ $tool['label'] }}</x-dropdown-item>
                    @endcan
                @endforeach
            </div>
        @endif
        <div class="dropdown-column">
            @can('develop')
                @can('manage', App\Community\Models\Ticket::class)
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
                @can('manage', App\Models\User::class)
                    {{--<x-dropdown-item :link="route('forum-topic.verify')">Forum Verification</x-dropdown-item>--}}
                    <x-dropdown-item :link="url('viewforum.php?f=0')">Forum Verification</x-dropdown-item>
                @endcan
                {{--@can('manage', App\Models\Event::class)
                    <h6 class="dropdown-header">Events</h6>
                @endcan--}}
                {{--@can('manage', App\Platform\Models\IntegrationRelease::class)
                    <x-dropdown-header>Releases</x-dropdown-header>
                    @can('manage', App\Platform\Models\Emulator::class)
                        <x-dropdown-item :link="route('emulator.index')" :active="request()->routeIs('emulator*')">Emulators</x-dropdown-item>
                    @endcan
                    <x-dropdown-item :link="route('integration.release.index')" :active="request()->routeIs('integration.release*')">Integration</x-dropdown-item>
                @endcan--}}
                @can('tool')
                    <div class="dropdown-header">Admin</div>
                    <x-dropdown-item :link="url('admin.php')">Admin Tools</x-dropdown-item>
                @endif
            @endif
        </div>
    </div>
</x-nav-dropdown>
