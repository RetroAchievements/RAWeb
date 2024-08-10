<?php

use App\Enums\Permissions;
use App\Models\User;

/** @var User $user */
$user = request()->user();

$tools = $settings['tools'] ?? null;

?>

<x-nav-dropdown :class="$class ?? ''" dropdown-class="dropdown-menu-right" :title="__('Manage')">
    <x-slot name="trigger">
        <x-fas-toolbox />
        <span class="ml-1 hidden sm:inline-block">{{ __('Manage') }}</span>
    </x-slot>

    <div class="md:flex">
        @if ($tools && !empty($tools))
            <div class="dropdown-column">
                <x-dropdown-header>{{ __('System') }}</x-dropdown-header>

                @foreach ($tools as $tool)
                    @can($tool['abilities'])
                        <x-dropdown-item
                            :href="!empty($tool['route']) ? route($tool['route']) : url($tool['url'])"
                        >
                            {{ $tool['label'] }}
                        </x-dropdown-item>
                    @endcan
                @endforeach
            </div>
        @endif

        <div class="dropdown-column">
            @can('develop')
                @can('manage', App\Models\Ticket::class)
                    <x-dropdown-header>{{ __('Development') }}</x-dropdown-header>
                    {{--<x-dropdown-item :href="route('triggers.ticket.index')">{{ __res('ticket') }}</x-dropdown-item>--}}
                    <x-dropdown-item :href="route('tickets.index')">{{ __res('ticket') }}</x-dropdown-item>
                    <x-dropdown-item :href="route('filament.admin.pages.most-reported-games')">Most Reported Games</x-dropdown-item>
                    <x-dropdown-item :href="url('achievementinspector.php')">Achievement Inspector</x-dropdown-item>
                @endcan

                @can('manage', App\Models\AchievementSetClaim::class)
                    <x-dropdown-item :href="route('claims.expiring')">Expiring Claims</x-dropdown-item>
                @endcan

                @can('manage', App\Models\GameHash::class)
                    <x-dropdown-item :href="route('filament.admin.resources.game-hashes.index')">Latest Linked Hashes</x-dropdown-item>
                @endcan
            @endcan

            @if ($user->Permissions >= Permissions::Developer)
                <x-dropdown-header>{{ __('Community') }}</x-dropdown-header>

                @can('manage', App\Models\News::class)
                    {{--<x-dropdown-item :href="route('news.index')">{{ __res('news') }}</x-dropdown-item>--}}
                    <x-dropdown-item :href="url('submitnews.php')">{{ __res('news') }}</x-dropdown-item>
                @endcan

                @if ($user->can('manage', User::class) || $user->Permissions === Permissions::Moderator)
                    {{--<x-dropdown-item :href="route('forum-topic.verify')">Forum Verification</x-dropdown-item>--}}
                    <x-dropdown-item :href="url('viewforum.php?f=0')">Forum Verification</x-dropdown-item>
                @endif

                {{--@can('manage', App\Models\Event::class)
                    <h6 class="dropdown-header">Events</h6>
                @endcan--}}
                {{--@can('manage', App\Models\IntegrationRelease::class)
                    <x-dropdown-header>Releases</x-dropdown-header>
                    @can('manage', App\Models\Emulator::class)
                        <x-dropdown-item :href="route('emulator.index')" :active="request()->routeIs('emulator*')">Emulators</x-dropdown-item>
                    @endcan
                    <x-dropdown-item :href="route('integration.release.index')" :active="request()->routeIs('integration.release*')">Integration</x-dropdown-item>
                @endcan--}}

                @if ($user->can('tool') || $user->Permissions === Permissions::Moderator)
                    <div class="dropdown-header">Admin</div>
                    <x-dropdown-item :href="url('admin.php')">Admin Tools</x-dropdown-item>
                @endif
            @endif
        </div>
    </div>
</x-nav-dropdown>
