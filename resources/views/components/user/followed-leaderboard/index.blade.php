@props([
    'user' => null, // User
])

<?php

use App\Platform\Enums\PlayerStatType;
use App\Platform\Services\FollowedUserLeaderboardService;

$followedUserLeaderboardService = new FollowedUserLeaderboardService();

$friendCount = $user->followedUsers->count();
$stats = $followedUserLeaderboardService->buildFollowedUserStats($user);

?>

<div class="component" x-data="{ activeTab: 'daily' }">
    <h3>Followed Users Ranking</h3>

    @if ($friendCount === 0)
        <p>
            You're not following anyone yet. Why not
            <a href="https://discord.gg/{{ config('services.discord.invite_id') }}">join us on Discord</a>
            and find someone to follow?
        </p>
    @else
        <div class="tab">
            <button
                class="active"
                :class="{ 'active': activeTab === 'daily' }"
                x-on:click="activeTab = 'daily'"
            >
                Daily
            </button>
            <button
                :class="{ 'active': activeTab === 'weekly' }"
                x-on:click="activeTab = 'weekly'"
            >
                Weekly
            </button>
            <button
                :class="{ 'active': activeTab === 'all-time' }"
                x-on:click="activeTab = 'all-time'"
            >
                All Time
            </button>
        </div>

        <div x-show="activeTab === 'daily'">
            <div class="flex flex-col gap-y-2">
                <x-user.followed-leaderboard.table :stats="$stats['statsDaily']" />
                
                <div class="flex w-full justify-end">
                    <a href="/globalRanking.php?t=0&f=1">more...</a>
                </div>
            </div>
        </div>

        <template x-if="activeTab === 'weekly'">
            <div class="flex flex-col gap-y-2">
                <x-user.followed-leaderboard.table :stats="$stats['statsWeekly']" />

                <div class="flex w-full justify-end">
                    <a href="/globalRanking.php?t=1&f=1">more...</a>
                </div>
            </div>
        </template>

        <template x-if="activeTab === 'all-time'">
            <div class="flex flex-col gap-y-2">
                <x-user.followed-leaderboard.table :stats="$stats['statsAllTime']" />

                <div class="flex w-full justify-end">
                    <a href="/globalRanking.php?t=2&f=1">more...</a>
                </div>
            </div>
        </template>
    @endif
</div>
