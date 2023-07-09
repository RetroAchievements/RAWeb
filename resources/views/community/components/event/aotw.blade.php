<?php

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\StaticData;

$staticData = StaticData::first();

if ($staticData === null) {
    return;
}

$achID = $staticData['Event_AOTW_AchievementID'];
$forumTopicID = $staticData['Event_AOTW_ForumID'];

$achData = GetAchievementData($achID);

$achievement = Achievement::find($achID);
$game = Game::find($achievement->GameID);
$gameAchievements = Achievement::where('GameID', $achievement->GameID)->published();

if (empty($achData)) {
    return;
}

$achievementId = $achievement->ID;
$achievementName = $achievement->Title;
$achievementDescription = $achievement->Description;
$achievementPoints = $achievement->Points;
$achievementRetroPoints = $achievement->TrueRatio;
$achievementBadgeName = $achievement->BadgeName;

$renderedGameTitle = renderGameTitle($game->Title);
$achievementIconSrc = media_asset("/Badge/$achievementBadgeName.png");
$gameSystemIconUrl = getSystemIconUrl($game->ConsoleID);
$consoleName = System::find($game->ConsoleID)->Name;
?>

<div class="component">
    <h3>Achievement of the Week</h3>

    <a href="{{ route('achievement.show', $achievement->ID) }}" draggable="false">
        <div class="transition-transform active:scale-95 bg-embed p-4 rounded border border-embed-highlight hover:text-white hover:border-menu-link hover:bg-embed-highlight">
            <div class="flex gap-x-2">
                <img src="{{ $achievementIconSrc }}" alt="Achievement of the week badge" width="64" height="64" class="w-16 h-16">

                <div>
                    <p class="font-semibold leading-4">{{ $achievement->Title }}</p>
                    <p class="text-2xs mb-1">5 (12) Points</p>
                    <p class="text-xs">{{ $achievement->Description }}</p>
                </div>
            </div>
        </div>
    </a>

    <div class="relative mt-[8px] mb-3 pb-[1px]">
        <hr class="border-embed-highlight w-full h-px absolute left-0 top-[2px]">
        <div class="bg-box absolute translate-x-1/2 right-1/2 -top-2 px-4 select-none pointer-events-none"><p>in</p></div>
    </div>

    <a href="{{ route('game.show', $achievement->GameID) }}" draggable="false">
        <div class="transition-transform active:scale-95 bg-embed p-4 rounded border border-embed-highlight hover:text-white hover:border-menu-link hover:bg-embed-highlight">
            <div class="flex gap-x-2">
                <img src="{{ media_asset($game->ImageIcon) }}" alt="Achievement of the week game badge" width="64" height="64" class="w-16 h-16">
    
                <div>
                    <div class="mb-2">
                        <p class="font-semibold mb-1 text-xs">{!! $renderedGameTitle !!}</p>
                        <div class="flex items-center gap-x-1">
                            <img src="{{ $gameSystemIconUrl }}" width="18" height="18" alt="Console icon">
                            <span class="block text-xs tracking-tighter">{{ $consoleName }}</span>
                        </div>
                    </div>

                    <p class="text-2xs subpixel-antialiased">{{ $gameAchievements->count() }} Achievements</p>
                    <p class="text-2xs subpixel-antialiased">
                        {{ localized_number($gameAchievements->sum('Points')) }}
                        ({{ localized_number($game->TotalTruePoints) }}) Points
                    </p>
                </div>
            </div>
        </div>
    </a>

    <a class="btn text-center py-2 w-full mt-2" href="/viewtopic.php?t={{ $forumTopicID }}">Join this event</a>
</div>
