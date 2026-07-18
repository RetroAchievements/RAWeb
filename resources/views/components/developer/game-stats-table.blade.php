@props([
    'beatenHardcoreAwards',
    'beatenCasualAwards',
    'completedAwards',
    'easiestGame',
    'hardestGame',
    'masteredAwards',
    'mostBeatenHardcoreGame',
    'mostBeatenCasualGame',
    'mostCompletedGame',
    'mostMasteredGame',
    'numGamesWithLeaderboards',
    'numGamesWithRichPresence',
    'numTotalLeaderboards',
    'ownAwards',
    'statsKind',
    'targetDeveloperDisplayName',
    'targetGameIds',
    'userMostBeatenHardcore',
    'userMostBeatenCasual',
    'userMostCompleted',
    'userMostMastered',
])

<table class="table-highlight">
    <thead>
        <tr class="do-not-highlight">
            <td colspan="2" align="center" class="text-h3 {{ $statsKind === 'any' ? 'pt-2' : 'pt-8' }}" role="heading" aria-level="2">
                @if ($statsKind === 'any')
                    Any Development
                @elseif ($statsKind === 'majority')
                    Majority Developer
                @elseif ($statsKind === 'sole')
                    Sole Developer
                @endif
            </td>
        </tr>
        <tr></tr>
        <tr class="do-not-highlight">
            <td colspan="2" align="center" class="pb-4">
                @if ($statsKind === 'any')
                    Stats below are for games that {{ $targetDeveloperDisplayName }} has published at least one achievement for.
                @elseif ($statsKind === 'majority')
                    Stats below are for games that {{ $targetDeveloperDisplayName }} has published at least half the achievements for.
                @elseif ($statsKind === 'sole')
                    Stats below are for games that {{ $targetDeveloperDisplayName }} has published all the achievements for.
                @endif
            </td>
        </tr>
    </thead>

    <tbody>
        <x-developer.game-stats-table-row headingLabel="Games Developed For:">
            {{ count($targetGameIds) }}
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Games with Rich Presence:">
            @if (empty($targetGameIds))
                N/A
            @else
                {{ $numGamesWithRichPresence }}
                &ndash;
                {{ number_format($numGamesWithRichPresence / count($targetGameIds) * 100, 2, '.', '') }}%
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Games with Leaderboards:">
            @if (empty($targetGameIds))
                N/A
            @else
                <div class="flex flex-col">
                    <span>
                        {{ $numGamesWithLeaderboards }}
                        &ndash;
                        {{ number_format($numGamesWithLeaderboards / count($targetGameIds) * 100, 2, '.', '') }}%
                    </span>
                    <span>
                        {{ $numTotalLeaderboards }} Unique Leaderboards
                    </span>
                </div>
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Easiest Game by Retro Ratio:">
            @if (empty($easiestGame))
                N/A
            @else
                <div class="flex flex-col">
                    <div>
                        {{ number_format($easiestGame['TotalTruePoints'] / $easiestGame['MaxPointsAvailable'], 2, '.', '') }}
                        &ndash;
                        {!! gameAvatar($easiestGame) !!}
                    </div>
                    <div>
                        {{ $easiestGame['MyAchievements'] }}
                        of
                        {{ $easiestGame['NumAchievements'] }}
                        Achievements Created
                    </div>
                </div>
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Hardest Game by Retro Ratio:">
            @if (empty($hardestGame))
                N/A
            @else
                <div class="flex flex-col">
                    <div>
                        {{ number_format($hardestGame['TotalTruePoints'] / $hardestGame['MaxPointsAvailable'], 2, '.', '') }}
                        &ndash;
                        {!! gameAvatar($hardestGame) !!}
                    </div>
                    <div>
                        {{ $hardestGame['MyAchievements'] }}
                        of
                        {{ $hardestGame['NumAchievements'] }}
                        Achievements Created
                    </div>
                </div>
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Beaten (casual/hardcore) Awards:">
            @if ($beatenCasualAwards === 0 && $beatenHardcoreAwards === 0)
                N/A
            @else
                {{ localized_number($beatenCasualAwards) }}
                <strong>({{ localized_number($beatenHardcoreAwards) }})</strong>
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Completed/Mastered Awards:">
            @if ($completedAwards === 0 && $masteredAwards === 0)
                N/A
            @else
                {{ localized_number($completedAwards) }}
                <strong>({{ localized_number($masteredAwards) }})</strong>
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Own Beaten (casual/hardcore) Awards:">
            @if (($ownAwards['BeatenCasual'] ?? 0) === 0 && ($ownAwards['BeatenHardcore'] ?? 0) === 0)
                N/A
            @else
                {{ $ownAwards['BeatenCasual'] }}
                <strong>({{ $ownAwards['BeatenHardcore'] }})</strong>
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Own Completed/Mastered Awards:">
            @if (($ownAwards['Completed'] ?? 0) === 0 && ($ownAwards['Mastered'] ?? 0) === 0)
                N/A
            @else
                {{ $ownAwards['Completed'] }}
                <strong>({{ $ownAwards['Mastered'] }})</strong>
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Most Beaten (casual) Game:">
            @if (($mostBeatenCasualGame['BeatenCasual'] ?? 0) === 0)
                N/A
            @else
                {{ localized_number($mostBeatenCasualGame['BeatenCasual'] ?? 0) }}
                &ndash;
                {!! gameAvatar($mostBeatenCasualGame) !!}
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Most Beaten Hardcore Game:">
            @if (($mostBeatenHardcoreGame['BeatenHardcore'] ?? 0) === 0)
                N/A
            @else
                {{ localized_number($mostBeatenHardcoreGame['BeatenHardcore'] ?? 0) }}
                &ndash;
                {!! gameAvatar($mostBeatenHardcoreGame) !!}
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Most Completed Game:">
            @if (($mostCompletedGame['Completed'] ?? 0) === 0)
                N/A
            @else
                {{ localized_number($mostCompletedGame['Completed'] ?? 0) }}
                &ndash;
                {!! gameAvatar($mostCompletedGame) !!}
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="Most Mastered Game:">
            @if (($mostMasteredGame['Mastered'] ?? 0) === 0)
                N/A
            @else
                {{ localized_number($mostMasteredGame['Mastered'] ?? 0) }}
                &ndash;
                {!! gameAvatar($mostMasteredGame) !!}
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="User with Most Beaten (casual) Awards:">
            @if (empty($userMostBeatenCasual))
                N/A
            @else
                {{ localized_number($userMostBeatenCasual['BeatenCasual']) }}
                &ndash;
                {!! userAvatar($userMostBeatenCasual['User']) !!}
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="User with Most Beaten Hardcore Awards:">
            @if (empty($userMostBeatenHardcore))
                N/A
            @else
                {{ localized_number($userMostBeatenHardcore['BeatenHardcore']) }}
                &ndash;
                {!! userAvatar($userMostBeatenHardcore['User']) !!}
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="User with Most Completed Awards:">
            @if (empty($userMostCompleted))
                N/A
            @else
                {{ localized_number($userMostCompleted['Completed']) }}
                &ndash;
                {!! userAvatar($userMostCompleted['User']) !!}
            @endif
        </x-developer.game-stats-table-row>

        <x-developer.game-stats-table-row headingLabel="User with Most Mastered Awards:">
            @if (empty($userMostMastered))
                N/A
            @else
                {{ localized_number($userMostMastered['Mastered']) }}
                &ndash;
                {!! userAvatar($userMostMastered['User']) !!}
            @endif
        </x-developer.game-stats-table-row>
    </tbody>
</table>
