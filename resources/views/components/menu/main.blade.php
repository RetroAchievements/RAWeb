<?php
/**
 * used for mobile presentation where dropdowns won't work in horizontally scrollable navbars
 */
$mobile ??= false;

$menuSystemsList = [
    [
        "Nintendo" => [
            ["systemName" => "Game Boy", "listID" => 4],
            ["systemName" => "Game Boy Color", "listID" => 6],
            ["systemName" => "Game Boy Advance", "listID" => 5],
            ["systemName" => "NES/Famicom", "listID" => 7],
            ["systemName" => "SNES/Super Famicom", "listID" => 3],
            ["systemName" => "Nintendo 64", "listID" => 2],
            ["systemName" => "Nintendo DS", "listID" => 18],
            ["systemName" => "Nintendo DSi", "listID" => 78],
            ["systemName" => "Pokemon Mini", "listID" => 24],
            ["systemName" => "Virtual Boy", "listID" => 28],
        ],
        "Sony" => [
            ["systemName" => "PlayStation", "listID" => 12],
            ["systemName" => "PlayStation 2", "listID" => 21],
            ["systemName" => "PlayStation Portable", "listID" => 41],
        ],
        "Atari" => [
            ["systemName" => "Atari 2600", "listID" => 25],
            ["systemName" => "Atari 7800", "listID" => 51],
            ["systemName" => "Atari Jaguar", "listID" => 17],
            ["systemName" => "Atari Jaguar CD", "listID" => 77],
            ["systemName" => "Atari Lynx", "listID" => 13],
        ],
    ],
    [
        "Sega" => [
            ["systemName" => "SG-1000", "listID" => 33],
            ["systemName" => "Master System", "listID" => 11],
            ["systemName" => "Game Gear", "listID" => 15],
            ["systemName" => "Genesis/Mega Drive", "listID" => 1],
            ["systemName" => "Sega CD", "listID" => 9],
            ["systemName" => "Sega 32X", "listID" => 10],
            ["systemName" => "Sega Saturn", "listID" => 39],
            ["systemName" => "Sega Dreamcast", "listID" => 40],
        ],
        "NEC" => [
            ["systemName" => "PC Engine/TurboGrafx-16", "listID" => 8],
            ["systemName" => "PC Engine CD/TurboGrafx-CD", "listID" => 76],
            ["systemName" => "PC-8000/8800", "listID" => 47],
            ["systemName" => "PC-FX", "listID" => 49],
        ],
    ],
    [
        "Other" => [
            ["systemName" => "3DO Interactive Multiplayer", "listID" => 43],
            ["systemName" => "Amstrad CPC", "listID" => 37],
            ["systemName" => "Apple II", "listID" => 38],
            ["systemName" => "Arcade", "listID" => 27],
            ["systemName" => "Arcadia 2001", "listID" => 73],
            ["systemName" => "Arduboy", "listID" => 71],
            ["systemName" => "ColecoVision", "listID" => 44],
            ["systemName" => "Elektor TV Games Computer", "listID" => 75],
            ["systemName" => "Fairchild Channel F", "listID" => 57],
            ["systemName" => "Intellivision", "listID" => 45],
            ["systemName" => "Interton VC 4000", "listID" => 74],
            ["systemName" => "Magnavox Odyssey 2", "listID" => 23],
            ["systemName" => "Mega Duck", "listID" => 69],
            ["systemName" => "MSX", "listID" => 29],
            ["systemName" => "Neo Geo Pocket", "listID" => 14],
            ["systemName" => "Uzebox", "listID" => 80],
            ["systemName" => "Vectrex", "listID" => 46],
            ["systemName" => "WASM-4", "listID" => 72],
            ["systemName" => "Watara Supervision", "listID" => 63],
            ["systemName" => "WonderSwan", "listID" => 53],
        ],

    ],
];
?>
{{--
<x-menu.play :mobile="$mobile" />
<x-menu.create :mobile="$mobile" />
<x-menu.community :mobile="$mobile" />
@if(!$mobile)
    <x-nav-item :link="route('download.index')">{{ __('Downloads') }}</x-nav-item>
    <x-nav-item :link="route('tool.index')">{{ __('Tools') }}</x-nav-item>
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
            @foreach($column as $manufacturer => $systems)
                    <x-dropdown-header>{{ $manufacturer }}</x-dropdown-header>
                    @foreach ($systems as $system)
                        <?php
                        $systemName = $system['systemName'];
                        $listId = $system['listID'];
                        $cleanSystemShortName = Str::lower(str_replace("/", "", config("systems.$listId.name_short")));
                        $iconName = Str::kebab($cleanSystemShortName);
                        ?>
                        <x-dropdown-item :link="url('gameList.php?c=' . $listId)">
                            <img src="{{ asset('assets/images/system/' . $iconName . '.png') }}" loading="lazy" width="16" height="16" alt='{{ $systemName }}'>
                            <span>{{ $systemName }}</span>
                        </x-dropdown-item>
                    @endforeach
            @endforeach
            </div>
        @endforeach
        <div class="dropdown-column">
            <x-dropdown-header>Miscellaneous</x-dropdown-header>
            <x-dropdown-item :link="url('gameList.php')">All Games</x-dropdown-item>
            {{--<x-dropdown-item link="popularGames.php">Most Played</x-dropdown-item>--}}
            <x-dropdown-item :link="url('gameSearch.php?p=0')">Hardest Games</x-dropdown-item>
            <x-dropdown-item :link="url('setRequestList.php')">Most Requested</x-dropdown-item>
            <x-dropdown-item :link="url('claimlist.php?s=9&f=8109')">New Sets & Revisions</x-dropdown-item>
            <x-dropdown-item :link="url('claimlist.php')">Sets in Progress</x-dropdown-item>
            <x-dropdown-item :link="url('random.php')">Random Set</x-dropdown-item>
            <x-dropdown-header>Hubs</x-dropdown-header>
            <x-dropdown-item :link="url('gameList.php?s=6&c=100&f=1')">Hub List</x-dropdown-item>
            <x-dropdown-item :link="url('game/6914')">Central Hub</x-dropdown-item>
            <x-dropdown-item :link="url('game/9553')">Genre & Subgenre Hub</x-dropdown-item>
            <x-dropdown-item :link="url('game/5771')">Series Hub</x-dropdown-item>
            <x-dropdown-item :link="url('game/3105')">Community Events Hub</x-dropdown-item>
            <x-dropdown-item :link="url('game/3273')">Developer Events Hub</x-dropdown-item>
        </div>
    </div>
</x-nav-dropdown>
<x-nav-dropdown :title="__res('achievement')">
    <x-slot name="trigger">
        <x-fas-trophy/>
        <span class="ml-1 hidden sm:inline-block">{{ __res('achievement') }}</span>
    </x-slot>
    <x-dropdown-item :link="url('achievementList.php')">All Achievements</x-dropdown-item>
    <div class="dropdown-divider"></div>
    {{--<x-dropdown-item :link="awardedList.php">Commonly Won Achievements</x-dropdown-item>--}}
    <x-dropdown-item :link="url('achievementList.php?s=4&p=2')">Easy Achievements</x-dropdown-item>
    <x-dropdown-item :link="url('achievementList.php?s=14&p=2')">Hardest Achievements</x-dropdown-item>
</x-nav-dropdown>
<x-nav-dropdown :title="__('Community')">
    <x-slot name="trigger">
        <x-fas-users/>
        <span class="ml-1 hidden sm:inline-block">{{ __('Community') }}</span>
    </x-slot>
    <x-dropdown-item :link="url('forum.php')">Forums</x-dropdown-item>
    <x-dropdown-item :link="url('viewforum.php?f=25')">Event Forums</x-dropdown-item>
    <x-dropdown-item :link="url('forumposthistory.php')">Recent Forum Posts</x-dropdown-item>
    <div class="dropdown-divider"></div>
    <x-dropdown-item :link="url('userList.php')">{{ __res('user') }}</x-dropdown-item>
    <x-dropdown-item :link="url('globalRanking.php')">Global Ranking</x-dropdown-item>
    <x-dropdown-item :link="url('recentMastery.php')">Recent Masteries</x-dropdown-item>
    <x-dropdown-item :link="url('developerstats.php')">Developer Stats</x-dropdown-item>
    <div class="dropdown-divider"></div>
    <x-dropdown-item link="https://news.retroachievements.org">RANews</x-dropdown-item>
    <x-dropdown-item link="https://github.com/RetroAchievements/guides/wiki">RAGuides Wiki</x-dropdown-item>
    <div class="dropdown-divider"></div>
    <x-dropdown-item link="https://docs.retroachievements.org">User Documentation</x-dropdown-item>
    <x-dropdown-item link="https://docs.retroachievements.org/Developer-Docs">Developer Documentation</x-dropdown-item>
</x-nav-dropdown>
<x-nav-item :link="url('download.php')" :title="__('Download')">
    <x-fas-download/>
    <span class="ml-1 hidden sm:inline-block">{{ __('Download') }}</span>
</x-nav-item>
