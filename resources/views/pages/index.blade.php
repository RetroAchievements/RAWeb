<?php

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\StaticData;
use Illuminate\Support\Carbon;
use function Laravel\Folio\{name};

name('home');

?>

@php
$staticData = StaticData::first();

if ($staticData === null) {
    return null;
}

$aotwAchievementId = $staticData['Event_AOTW_AchievementID'];
$eventForumTopicId = $staticData['Event_AOTW_ForumID'];
$achievement = Achievement::find($aotwAchievementId);

if (!$achievement) {
    return null;
}

$game = Game::find($achievement->GameID);
$consoleName = System::find($game->ConsoleID)->Name;

$currentEventMetadata = [
    'eventAchievement' => $achievement,
    'eventGame' => $game,
    'eventConsoleName' => $consoleName,
    'eventForumTopicId' => $eventForumTopicId,
];
@endphp

<x-app-layout>
    @guest
        <x-content.welcome />
    @elseif(auth()->user()?->isNew())
        <x-content.getting-started />
    @endguest

    <x-news.carousel-2 />
    <x-claims.finished-claims count="6" />

    <x-active-players />

    <div class="mb-8">
        <x-user.online-count-chart />
    </div>

    <x-claims.new-claims count="5" />
    <x-forum-recent-posts />

    <x-slot name="sidebar">
        <x-content.top-links />
        <div class="mt-6 flex flex-col gap-y-3">
            @if(isset($eventAchievement))
                <x-event.aotw
                    :achievement="$eventAchievement"
                    :game="$eventGame"
                    :consoleName="$eventConsoleName"
                    :forumTopicId="$eventForumTopicId"
                />
            @endif
            <x-global-statistics />
        </div>
    </x-slot>
</x-app-layout>
