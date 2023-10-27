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

$renderedGameTitle = renderGameTitle($game->Title);
$achievementIconSrc = media_asset("/Badge/$achievementBadgeName.png");
$gameSystemIconUrl = getSystemIconUrl($game->ConsoleID);

$achievementUrl = route('achievement.show', $achievement->ID);
$gameUrl = route('game.show', $achievement->GameID);
$gameSystemUrl = route('game.index', ['c' => $game->ConsoleID]);
?>

<div class="component">
    <h3>Achievement of the Week</h3>

    <div class="bg-embed p-4 rounded border border-embed-highlight">
        <div class="flex gap-x-2 mb-3 text-text">
            <a href={{ $achievementUrl }}>
                <img src="{{ $achievementIconSrc }}" alt="Achievement of the week badge" width="64" height="64" class="w-16 h-16">
            </a>

            <div>
                <a href={{ $achievementUrl }} class="font-semibold leading-4 text-link">
                    <x-achievement.title :rawTitle="$achievementName" />
                </a>
                <p class="text-xs mb-1">{{ $achievementPoints }} <span class="TrueRatio">({{ $achievementRetroPoints }})</span> Points</p>
                <p class="text-xs">{{ $achievement->Description }}</p>
            </div>
        </div>

        <hr class="border-embed-highlight mt-2 mb-3">

        <div class="gap-x-2 flex relative">
            {{-- Keep the image and game title in a single tooltipped container. Do not tooltip the console name. --}}
            <a 
                href="{{ $gameUrl }}" 
                x-data="tooltipComponent($el, {dynamicType: 'game', dynamicId: '{{ $achievement->GameID }}'})"
                @mouseover="showTooltip($event)"
                @mouseleave="hideTooltip"
                @mousemove="trackMouseMovement($event)"
            >
                <img 
                    src="{{ media_asset($game->ImageIcon) }}" 
                    alt="Achievement of the week game badge" 
                    width="32" 
                    height="32" 
                    class="w-8 h-8"
                >

                <p class="absolute max-w-fit pl-4 top-[-4px] left-6 font-semibold mb-0.5 text-xs">
                    {!! $renderedGameTitle !!}
                </p>
            </a>

            <div>
                {{-- Provide invisible space to slide the console underneath --}}
                <p class="invisible max-w-fit pl-4 font-semibold mb-0.5 text-xs">{!! $renderedGameTitle !!}</p>

                <a href="{{ $gameSystemUrl }}" class="flex items-center gap-x-1 -mt-1">
                    <img src="{{ $gameSystemIconUrl }}" width="18" height="18" alt="Console icon">
                    <span class="block text-xs tracking-tighter">{{ $consoleName }}</span>
                </a>
            </div>
        </div>
    </div>

    <a class="btn text-center py-2 w-full mt-2" href="/viewtopic.php?t={{ $forumTopicId }}">Learn more about this event</a>
</div>
