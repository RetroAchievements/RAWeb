<?php

use App\Enums\PlayerGameActivityEventType;
use App\Enums\PlayerGameActivitySessionType;
use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Services\PlayerGameActivityPageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,game']);
name('user.game.activity');

render(function (View $view, User $user, Game $game, PlayerGameActivityPageService $pageService) {
    if (!Auth::user()->can('viewSessionHistory', App\Models\PlayerGame::class)) {
        abort(401);
    }

    return $view->with($pageService->buildViewData($user, $game));
});

?>

@props([
    'activity' => null, // PlayerGameActivityService
    'estimated' => '',
    'sessionInfo' => '',
    'summary' => [],
    'userAgentService' => null, // UserAgentService
    'userProgress' => 'n/a',
])

<x-app-layout pageTitle="{{ $user->User }}'s activity for {{ $game->Title }}">
    <x-user.breadcrumbs
        :targetUsername="$user->User"
        :parentPage="$game->Title"
        :parentPageUrl="$game->permalink"
        currentPage="Activity"
    />

    <div class="mt-3 w-full relative flex gap-x-3">
        {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Game Activity: {{ $user->User }}</h1>
    </div>
@if (!empty($activity->sessions))
    <div>
        @if ($summary['totalPlaytime'] != $summary['achievementPlaytime'])
            <p>
                <span class="font-bold">Total Playtime:</span>
                <span>{{ formatHMS($summary['totalPlaytime']) }}{{ $estimated }}</span>
            </p>
        @endif
        <p>
            <span class="font-bold">Achievement Playtime:</span>
            <span>{{ formatHMS($summary['achievementPlaytime']) }}{{ $estimated }}</span>
        </p>
        <p>
            <span class="font-bold">Achievement Sessions:</span>
            <span>{{ $sessionInfo }}</span>
        </p>
        <p>
            <span class="font-bold">Achievements Unlocked:</span>
            <span>{{ $activity->achievementsUnlocked }}{{ $userProgress }}</span>
        </p>
        <p>
            <x-user.client-list :clients="$activity->getClientBreakdown($userAgentService)" />
        </p>
    </div>
@endif

<x-user.game-activity
    :game="$game"
    :user="$user"
    :sessions="$activity->sessions"
    :userAgentService="$userAgentService"
/>

</x-app-layout>
