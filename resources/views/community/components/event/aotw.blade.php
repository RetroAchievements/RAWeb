@props([
    'achievement' => [],
    'game' => [],
    'consoleName' => '',
    'forumTopicId' => 0,
])

<?php
$achievementId = $achievement->ID;
$achievementName = $achievement->Title;
$achievementDescription = $achievement->Description;
$achievementPoints = $achievement->Points;
$achievementRetroPoints = $achievement->TrueRatio;
$achievementBadgeName = $achievement->BadgeName;

$renderedAchievementTitle = renderAchievementTitle($achievementName);
$renderedGameTitle = renderGameTitle($game->Title);
$achievementIconSrc = media_asset("/Badge/$achievementBadgeName.png");
$gameSystemIconUrl = getSystemIconUrl($game->ConsoleID);

$achievementUrl = route('achievement.show', $achievement->ID);
?>

<div class="component">
    <h3>Achievement of the Week</h3>

    <div class="bg-embed p-4 rounded border border-embed-highlight">
        <div class="flex gap-x-2 mb-3 text-text">
            <a href={{ $achievementUrl }}>
                <img src="{{ $achievementIconSrc }}" alt="Achievement of the week badge" width="64" height="64" class="w-16 h-16">
            </a>

            <div>
                <a href={{ $achievementUrl }} class="font-semibold leading-4 text-link">{!! $renderedAchievementTitle !!}</a>
                <p class="text-xs mb-1">5 <span class="TrueRatio">(12)</span> Points</p>
                <p class="text-xs">{{ $achievement->Description }}</p>
            </div>
        </div>

        <hr class="border-embed-highlight mt-2 mb-3">

        <a href="{{ route('game.show', $achievement->GameID) }}" class="group flex gap-x-2">
            <img src="{{ media_asset($game->ImageIcon) }}" alt="Achievement of the week game badge" width="32" height="32" class="w-8 h-8">
            <div class="-mt-1">
                <p class="font-semibold mb-0.5 text-xs text-link group-hover:text-link-hover">{!! $renderedGameTitle !!}</p>
                <div class="flex items-center gap-x-1">
                    <img src="{{ $gameSystemIconUrl }}" width="18" height="18" alt="Console icon">
                    <span class="block text-xs tracking-tighter">{{ $consoleName }}</span>
                </div>
            </div>
        </a>
    </div>

    <a class="btn text-center py-2 w-full mt-2" href="/viewtopic.php?t={{ $forumTopicId }}">Learn more about this event</a>
</div>
