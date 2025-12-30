<?php

use App\Enums\Permissions;
use App\Models\User;

/** @var User $user */
$user = request()->user();

$tools = $settings['tools'] ?? [];
$visibleTools = collect($tools)->filter(fn($tool) => $user?->can($tool['abilities']));

?>

<x-nav-dropdown
    :class="$class ?? ''"
    dropdown-class="dropdown-menu-right"
    :title="__('Manage')"
    :desktopHref="route('filament.admin.pages.dashboard')"
>
    <x-slot name="trigger">
        <x-fas-toolbox />
        <span class="ml-1 hidden sm:inline-block">{{ __('Manage') }}</span>
    </x-slot>

    <div class="md:flex">
        @if ($visibleTools->isNotEmpty())
            <div class="dropdown-column">
                <x-dropdown-header>{{ __('System') }}</x-dropdown-header>

                @foreach ($visibleTools as $tool)
                    <x-dropdown-item
                        :href="!empty($tool['route']) ? route($tool['route']) : url($tool['url'])"
                    >
                        {{ $tool['label'] }}
                    </x-dropdown-item>
                @endforeach
            </div>
        @endif

        <div class="dropdown-column">
            @can('develop')
                @can('manage', App\Models\Ticket::class)
                    <x-dropdown-header>{{ __('Development') }}</x-dropdown-header>
                    <x-dropdown-item :href="route('tickets.index')">{{ __res('ticket') }}</x-dropdown-item>
                    <x-dropdown-item :href="route('filament.admin.pages.most-reported-games')">Most Reported Games</x-dropdown-item>
                    <x-dropdown-item :href="url('achievementinspector.php')">Achievement Inspector</x-dropdown-item>
                @endcan

                @can('manage', App\Models\AchievementSetClaim::class)
                    <x-dropdown-item
                        :href="route('filament.admin.resources.achievement-set-claims.index', ['tableSortColumn' => 'Finished', 'tableSortDirection' => 'asc'])"
                    >
                        Expiring Claims
                    </x-dropdown-item>
                @endcan

                @can('manage', App\Models\GameHash::class)
                    <x-dropdown-item :href="route('filament.admin.resources.game-hashes.index')">Latest Linked Hashes</x-dropdown-item>
                @endcan
            @endcan

            @if ($user->Permissions >= Permissions::Developer)
                <x-dropdown-header>{{ __('Community') }}</x-dropdown-header>

                @can('manage', App\Models\News::class)
                    <x-dropdown-item :href="route('filament.admin.resources.news.index')">{{ __res('news') }}</x-dropdown-item>
                @endcan

                @if ($user->can('manage', User::class) || $user->Permissions === Permissions::Moderator)
                    <x-dropdown-item :href="url('viewforum.php?f=0')">Forum Verification</x-dropdown-item>
                @endif

                @if ($user->can('tool') || $user->Permissions === Permissions::Moderator)
                    <div class="dropdown-header">Admin</div>
                    <x-dropdown-item :href="url('admin.php')">Admin Tools</x-dropdown-item>
                @endif
            @endif
        </div>
    </div>
</x-nav-dropdown>
