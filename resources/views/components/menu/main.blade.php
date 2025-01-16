<?php

use App\Models\System;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * used for mobile presentation where dropdowns won't work in horizontally scrollable navbars
 */
$mobile ??= false;

/* caching this saves about 750us on every page load */
$menuSystemsList = Cache::remember(CacheKey::SystemMenuList, Carbon::now()->addHours(1), function() {
    $systems = System::gameSystems()->active()
        ->orderBy('order_column')
        ->get();

    $menuSystemsList = [
        ['Nintendo' => [], 'Sony' => [], 'Atari' => []],
        ['Sega' => [], 'NEC' => [], 'SNK' => []],
    ];

    $otherManufacturers = [];
    foreach ($systems as $system) {
        $found = false;
        foreach ($menuSystemsList as &$column) {
            if (array_key_exists($system->manufacturer, $column)) {
                $column[$system->manufacturer][] = $system;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $otherManufacturers[] = $system;
        }
    }

    if (!empty($otherManufacturers)) {
        usort($otherManufacturers, fn ($a, $b) => strcasecmp($a['Name'], $b['Name']));
        $menuSystemsList[] = ['Others' => $otherManufacturers];
    }

    return $menuSystemsList;
});

?>
{{--
<x-menu.play :mobile="$mobile" />
<x-menu.create :mobile="$mobile" />
<x-menu.community :mobile="$mobile" />
@if(!$mobile)
    <x-nav-item :href="route('download.index')">{{ __('Downloads') }}</x-nav-item>
    <x-nav-item :href="route('tool.index')">{{ __('Tools') }}</x-nav-item>
@endif
--}}
<x-nav-dropdown :title="__res('game')">
    <x-slot name="trigger">
        <x-fas-gamepad/>
        <span class="ml-1 hidden sm:inline-block">{{ __res('game') }}</span>
    </x-slot>
    <div class="md:flex">
        @foreach ($menuSystemsList as $column)
            <div class="dropdown-column">
            @foreach ($column as $manufacturer => $manufacturerSystems)
                    <x-dropdown-header>{{ $manufacturer }}</x-dropdown-header>
                    @foreach ($manufacturerSystems as $system)
                        <x-dropdown-item :href="route('system.game.index', ['system' => $system->ID])">
                            <img src="{!! getSystemIconUrl($system) !!}" loading="lazy" width="16" height="16" alt='{{ $system->Name }}'>
                            <span>{{ $system->Name }}</span>
                        </x-dropdown-item>
                    @endforeach
            @endforeach
            </div>
        @endforeach
        <div class="dropdown-column">
            <x-dropdown-header>Miscellaneous</x-dropdown-header>
            <x-dropdown-item :href="route('game.index')">All Games</x-dropdown-item>
            {{--<x-dropdown-item href="popularGames.php">Most Played</x-dropdown-item>--}}
            <x-dropdown-item :href="url('gameSearch.php?p=0')">Hardest Games</x-dropdown-item>
            <x-dropdown-item :href="url('setRequestList.php')">Most Requested</x-dropdown-item>
            <x-dropdown-item :href="route('claims.completed')">New Sets & Revisions</x-dropdown-item>
            <x-dropdown-item :href="route('claims.active')">Sets in Progress</x-dropdown-item>
            <x-dropdown-item :href="route('game.random')">Random Set</x-dropdown-item>
            <x-dropdown-header>Hubs</x-dropdown-header>
            <x-dropdown-item :href="route('hub.show', ['gameSet' => 1])">Central Hub</x-dropdown-item>
            <x-dropdown-item :href="route('hub.show', ['gameSet' => 2])">Genre & Subgenre Hub</x-dropdown-item>
            <x-dropdown-item :href="route('hub.show', ['gameSet' => 3])">Series Hub</x-dropdown-item>
            <x-dropdown-item :href="route('hub.show', ['gameSet' => 4])">Community Events Hub</x-dropdown-item>
            <x-dropdown-item :href="route('hub.show', ['gameSet' => 5])">Developer Events Hub</x-dropdown-item>
        </div>
    </div>
</x-nav-dropdown>
<x-nav-dropdown :title="__res('achievement')">
    <x-slot name="trigger">
        <x-fas-trophy/>
        <span class="ml-1 hidden sm:inline-block">{{ __res('achievement') }}</span>
    </x-slot>
    <x-dropdown-item :href="url('achievementList.php')">All Achievements</x-dropdown-item>
    <div class="dropdown-divider"></div>
    {{--<x-dropdown-item :href="awardedList.php">Commonly Won Achievements</x-dropdown-item>--}}
    <x-dropdown-item :href="url('achievementList.php?s=4&p=2')">Easy Achievements</x-dropdown-item>
    <x-dropdown-item :href="url('achievementList.php?s=14&p=2')">Hardest Achievements</x-dropdown-item>
</x-nav-dropdown>
<x-nav-dropdown :title="__('Community')">
    <x-slot name="trigger">
        <x-fas-users/>
        <span class="ml-1 hidden sm:inline-block">{{ __('Community') }}</span>
    </x-slot>
    <x-dropdown-item :href="url('forum.php')">Forums</x-dropdown-item>
    <x-dropdown-item :href="url('viewforum.php?f=25')">Event Forums</x-dropdown-item>
    <x-dropdown-item :href="route('forum.recent-posts')">Recent Forum Posts</x-dropdown-item>
    <div class="dropdown-divider"></div>
    <x-dropdown-item :href="url('userList.php')">{{ __res('user') }}</x-dropdown-item>
    <x-dropdown-item :href="url('globalRanking.php')">Global Points Ranking</x-dropdown-item>
    <x-dropdown-item :href="route('ranking.beaten-games')">Global Beaten Games Ranking</x-dropdown-item>
    <x-dropdown-item :href="url('recentMastery.php')">Recent Game Awards</x-dropdown-item>
    <x-dropdown-item :href="url('developerstats.php')">Developer Stats</x-dropdown-item>
    <div class="dropdown-divider"></div>
    <x-dropdown-item href="https://news.retroachievements.org">RANews</x-dropdown-item>
    <x-dropdown-item href="https://github.com/RetroAchievements/guides/wiki">RAGuides Wiki</x-dropdown-item>
    <div class="dropdown-divider"></div>
    <x-dropdown-item href="https://docs.retroachievements.org">User Documentation</x-dropdown-item>
    <x-dropdown-item href="https://docs.retroachievements.org/developer-docs/">Developer Documentation</x-dropdown-item>
</x-nav-dropdown>
<x-nav-item :href="url('download.php')" :title="__('Download')">
    <x-fas-download/>
    <span class="ml-1 hidden sm:inline-block">{{ __('Download') }}</span>
</x-nav-item>
