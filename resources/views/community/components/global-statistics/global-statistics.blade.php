<div class="component statistics !mb-0">
    <h3>Statistics</h3>

    <div class="infobox">
        <div class="w-full">
            <div class="grid grid-cols-2 gap-px mb-2">
                <x-global-statistics.stat-embed label="Games" :count="$numGames" href="{{ route('game.index', ['s' => 1]) }}" />
                <x-global-statistics.stat-embed label="Achievements" :count="$numAchievements" href="/achievementList.php" />
                @hasfeature("beat")
                    <x-global-statistics.stat-embed label="Games Mastered" :count="$numHardcoreMasteryAwards" href="/recentMastery.php?t=1&m=1" />
                    <x-global-statistics.stat-embed label="Games Beaten" :count="$numHardcoreGameBeatenAwards" href="/recentMastery.php?t=8&m=1" />
                @endhasfeature
                <x-global-statistics.stat-embed label="Registered Players" :count="$numRegisteredPlayers" href="/userList.php" />
                <x-global-statistics.stat-embed label="Achievement Unlocks" :count="$numAwarded" href="/recentMastery.php" />
            </div>
        </div>

        <div class="w-full h-16 flex flex-col justify-center items-center">
            <p>Points earned since March 2nd, 2013</p>
            <span class="text-2xl">{{ number_format($totalPointsEarned) }}</span>
        </div>
    </div>

    <hr class="mt-4 mb-5 border-embed-highlight">

    <div class="flex flex-col gap-y-6">
        <x-global-statistics.recent-game-progress
            headingLabel="Most recent game mastered"
            :game="$lastMasteredGame"
            :userId="$lastMasteredUserId"
            :timestamp="$lastMasteredTimeAgo"
        />

        @hasfeature("beat")
            <x-global-statistics.recent-game-progress
                headingLabel="Most recent game beaten"
                :game="$lastBeatenGame"
                :userId="$lastBeatenUserId"
                :timestamp="$lastBeatenTimeAgo"
            />
        @endhasfeature
    </div>

    @if ($lastRegisteredUser)
        <hr class="mt-4 mb-5 border-embed-highlight">

        <div class="w-full flex flex-col justify-center items-center">
            <p>Newest user</p>

            <div class="flex items-center gap-x-1">
                <a href={{ route('user.show', $lastRegisteredUser) }}>{{ $lastRegisteredUser }}</a>
                @if ($lastRegisteredUserTimeAgo)
                    <span class="text-2xs">({{ $lastRegisteredUserTimeAgo }})</span>
                @endif
            </div>
        </div>
    @endif
</div>