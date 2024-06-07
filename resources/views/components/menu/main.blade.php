<?php
/**
 * used for mobile presentation where dropdowns won't work in horizontally scrollable navbars
 */
$mobile ??= false;

$menuSystemsList = [
    [
        "Nintendo" => [
            4, // Game Boy
            6, // Game Boy Color
            5, // Game Boy Advance
            7, // NES/Famicom
            3, // SNES/Super Famicom
            2, // Nintendo 64
            18, // Nintendo DS
            78, // Nintendo DSi
            24, // Pokemon Mini
            28, // Virtual Boy
        ],
        "Sony" => [
            12, // PlayStation
            21, // PlayStation 2
            41, // PlayStation Portable
        ],
        "Atari" => [
            25, // Atari 2600
            51, // Atari 7800
            17, // Atari Jaguar
            77, // Atari Jaguar CD
            13, // Atari Lynx
        ],
    ],
    [
        "Sega" => [
            33, // SG-1000
            11, // Master System
            15, // Game Gear
            1, // Genesis/Mega Drive
            9, // Sega CD
            10, // Sega 32X
            39, // Sega Saturn
            40, // Sega Dreamcast
        ],
        "NEC" => [
            8, // PC Engine/TurboGrafx-16
            76, // PC Engine CD/TurboGrafx-CD
            47, // PC-8000/8800
            49, // PC-FX
        ],
        "SNK" => [
            56, // Neo Geo CD
            14, // Neo Geo Pocket
        ],
    ],
    [
        "Other" => [
            43, // 3DO Interactive Multiplayer
            37, // Amstrad CPC
            38, // Apple II
            27, // Arcade
            73, // Arcadia 2001
            71, // Arduboy
            44, // ColecoVision
            75, // Elektor TV Games Computer
            57, // Fairchild Channel F
            45, // Intellivision
            74, // Interton VC 4000
            23, // Magnavox Odyssey 2
            69, // Mega Duck
            29, // MSX
            102, // Standalone
            80, // Uzebox
            46, // Vectrex
            72, // WASM-4
            63, // Watara Supervision
            53, // WonderSwan
        ],

    ],
];
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
            @foreach ($column as $manufacturer => $systemIds)
                    <x-dropdown-header>{{ $manufacturer }}</x-dropdown-header>
                    @foreach ($systemIds as $systemId)
                        <?php
                        $systemName = config("systems.$systemId.name");
                        $iconName = Str::kebab(Str::lower(str_replace("/", "", config("systems.$systemId.name_short"))));
                        ?>
                        <x-dropdown-item :href="route('system.game.index', ['system' => $systemId])">
                            <img src="{{ asset('assets/images/system/' . $iconName . '.png') }}" loading="lazy" width="16" height="16" alt='{{ $systemName }}'>
                            <span>{{ $systemName }}</span>
                        </x-dropdown-item>
                    @endforeach
            @endforeach
            </div>
        @endforeach
        <div class="dropdown-column">
            <x-dropdown-header>Miscellaneous</x-dropdown-header>
            <x-dropdown-item :href="url('gameList.php')">All Games</x-dropdown-item>
            {{--<x-dropdown-item href="popularGames.php">Most Played</x-dropdown-item>--}}
            <x-dropdown-item :href="url('gameSearch.php?p=0')">Hardest Games</x-dropdown-item>
            <x-dropdown-item :href="url('setRequestList.php')">Most Requested</x-dropdown-item>
            <x-dropdown-item :href="route('claims.completed')">New Sets & Revisions</x-dropdown-item>
            <x-dropdown-item :href="route('claims.active')">Sets in Progress</x-dropdown-item>
            <x-dropdown-item :href="url('random.php')">Random Set</x-dropdown-item>
            <x-dropdown-header>Hubs</x-dropdown-header>
            <x-dropdown-item :href="url('gameList.php?s=6&c=100&f=1')">Hub List</x-dropdown-item>
            <x-dropdown-item :href="url('game/6914')">Central Hub</x-dropdown-item>
            <x-dropdown-item :href="url('game/9553')">Genre & Subgenre Hub</x-dropdown-item>
            <x-dropdown-item :href="url('game/5771')">Series Hub</x-dropdown-item>
            <x-dropdown-item :href="url('game/3105')">Community Events Hub</x-dropdown-item>
            <x-dropdown-item :href="url('game/3273')">Developer Events Hub</x-dropdown-item>
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
    <x-dropdown-item href="https://docs.retroachievements.org/Developer-Docs">Developer Documentation</x-dropdown-item>
</x-nav-dropdown>
<x-nav-item :href="url('download.php')" :title="__('Download')">
    <x-fas-download/>
    <span class="ml-1 hidden sm:inline-block">{{ __('Download') }}</span>
</x-nav-item>
