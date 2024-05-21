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
$eventAchievement = null;
$eventGame = null;
$eventConsoleName = null;
$eventForumTopicId = null;

$staticData = StaticData::first();
if ($staticData) {
    $aotwAchievementId = $staticData['Event_AOTW_AchievementID'];
    $eventForumTopicId = $staticData['Event_AOTW_ForumID'];
    $eventAchievement = Achievement::find($aotwAchievementId);

    if ($eventAchievement) {
        $eventGame =  Game::find($eventAchievement->GameID);
        $eventConsoleName = System::find($eventGame->ConsoleID)->Name;
    }
}
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
    <x-forum-recent-activity />

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
