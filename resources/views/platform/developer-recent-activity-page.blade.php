@props([
    'awardsContributed' => 0,
    'foundTargetUser' => null, // User
    'leaderboardEntriesContributed' => 0,
    'recentAwards' => null, // Collection
    'recentLeaderboardEntries' => null, // Collection
    'recentUnlocks' => null, // Collection
    'targetGameIds' => [],
    'targetUserUnlocksContributed' => 0,
    'targetUserPointsContributed' => 0,
])

<x-app-layout
    pageTitle="{{ $foundTargetUser->User }} - Developer Feed"
    pageDescription="View recent activity for achievements contributed by {{ $foundTargetUser->User }} on RetroAchievements"
>
    <x-user.breadcrumbs :targetUsername="$foundTargetUser->User" currentPage="Developer Feed" />

    <div class="mt-3 mb-6 w-full flex gap-x-3">
        {!! userAvatar($foundTargetUser->User, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $foundTargetUser->User }}'s Developer Feed</h1>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 mb-6 gap-4">
        <x-developer-recent-activity.stat-box
            headingLabel="Unlocks contributed"
            :value="$targetUserUnlocksContributed"
        />

        <x-developer-recent-activity.stat-box
            headingLabel="Points contributed"
            :value="$targetUserPointsContributed"
        />

        <x-developer-recent-activity.stat-box
            headingLabel="Awards contributed"
            :value="$awardsContributed"
        />

        <x-developer-recent-activity.stat-box
            headingLabel="Leaderboard entries contributed"
            :value="$leaderboardEntriesContributed"
        />
    </div>

    <div class="grid gap-y-12">
        <div>
            <h2 class="text-h4">Current Players</h2>
            <x-active-players :targetGameIds="$targetGameIds" variant="focused" />
        </div>

        <x-developer-recent-activity.recently-obtained-achievements
            :recentUnlocks="$recentUnlocks"
        />

        <x-developer-recent-activity.recently-obtained-awards
            :recentAwards="$recentAwards"
        />

        <x-developer-recent-activity.recent-leaderboard-entries
            :recentLeaderboardEntries="$recentLeaderboardEntries"
        />
    </div>
</x-app-layout>
