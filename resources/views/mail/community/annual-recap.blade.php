@php

$countThing = function (?int $count, string $thing) {
    if ($count) {
        return $count . ' ' . Str::plural($thing, $count);
    } else {
        return '';
    }
};

$hardcorePointsClause = $countThing($recapData['hardcorePointsEarned'], 'hardcore point');
$softcorePointsClause = $countThing($recapData['softcorePointsEarned'], 'softcore point');

if ($hardcorePointsClause && $softcorePointsClause) {
    $softcorePointsClause = ' and ' . $softcorePointsClause;
}

$leaderboardsSubmittedClause = $recapData['leaderboardsSubmitted'] > 0 ?
    'You submitted new scores for ' . $countThing($recapData['leaderboardsSubmitted'], 'leaderboard') . '.' :
    '';

if ($recapData['playedSystems'] === 1) {
    $playedSystemsClause = "on 1 system";
} else {
    $playedSystemsClause = "across {$recapData['playedSystems']} systems. {$recapData['mostPlayedSystemPlaytime']} of that were playing {$recapData['mostPlayedSystem']} games";
}

$masteryClause = '';
$numMasteries = $countThing($recapData['numMasteries'], 'game');
$numBeatenHardcore = $countThing($recapData['numBeatenHardcore'], 'game');
if ($numMasteries) {
    if ($numBeatenHardcore) {
        $masteryClause = "You mastered $numMasteries, and beat an additional $numBeatenHardcore on hardcore. ";
    } else {
        $masteryClause = "You mastered $numMasteries. ";
    }
} elseif ($numBeatenHardcore) {
    $masteryClause = "You beat $numBeatenHardcore on hardcore. ";
}

$completionClause = '';
$numCompletions = $countThing($recapData['numCompletions'], 'game');
$numBeaten = $countThing($recapData['numBeaten'], 'game');
if ($numCompletions) {
    if ($numBeaten) {
        $completionClause = "You completed $numCompletions, and beat an additional $numBeaten on softcore.";
    } else {
        $completionClause = "You completed $numCompletions.";
    }
} elseif ($numBeaten) {
    $completionClause = "You beat $numBeaten on softcore.";
}

@endphp

<x-mail::message>
Congratulations {{ $user->display_name }}!

In {{ $recapData['year'] }}, you played {{ $countThing($recapData['gamesPlayed'], 'game') }} on <a href="{{ route('home') }}">retroachievements.org</a> and unlocked {{ $countThing($recapData['achievementsUnlocked'], 'achievement') }}, earning you {{ $hardcorePointsClause }}{{ $softcorePointsClause }}. {{ $leaderboardsSubmittedClause }}

You spent {{ $recapData['totalPlaytime'] }} playing games {{ $playedSystemsClause }}.

{{ $masteryClause }}{{ $completionClause }}

@if ($recapData['mostPlayedGame'])
<x-mail::image-panel
    src="{{ media_asset($recapData['mostPlayedGame']->ImageIcon) }}"
    alt="{{ $recapData['mostPlayedGame']->Title }} game badge"
    url="{{ route('game.show', $recapData['mostPlayedGame']) }}"
    width="64"
    height="64"
>
    Your most played game was <a href="{{ route('game.show', $recapData['mostPlayedGame']) }}">{{ $recapData['mostPlayedGame']->Title }}</a> at {{ $recapData['mostPlayedGamePlaytime'] }}.
</x-mail::panel>
@endif

@if ($recapData['rarestHardcoreAchievement'])
<x-mail::image-panel
    src="{{ $recapData['rarestHardcoreAchievement']->badge_unlocked_url }}"
    alt="{{ $recapData['rarestHardcoreAchievement']->Title }} achievement badge"
    url="{{ route('achievement.show', $recapData['rarestHardcoreAchievement']) }}"
    width="64"
    height="64"
>
    Your rarest achievement earned was <a href="{{ route('achievement.show', $recapData['rarestHardcoreAchievement']) }}">{{ $recapData['rarestHardcoreAchievement']->Title }}</a> from {{ $recapData['rarestHardcoreAchievement']->Game->Title }}, which has only been earned in hardcore by {{ $recapData['rarestHardcoreAchievementEarnRate'] }}% of players.
</x-mail::image-panel>
@elseif ($recapData['rarestSoftcoreAchievement'])
<x-mail::image-panel
    src="{{ $recapData['rarestSoftcoreAchievement']->badge_unlocked_url }}"
    alt="{{ $recapData['rarestSoftcoreAchievement']->Title }} achievement badge"
    url="{{ route('achievement.show', $recapData['rarestSoftcoreAchievement']) }}"
    width="64"
    height="64"
>
    Your rarest achievement earned was <a href="{{ route('achievement.show', $recapData['rarestSoftcoreAchievement']) }}">{{ $recapData['rarestHardcoreAchievement']->Title }}</a> from {{ $recapData['rarestHardcoreAchievement']->Game->Title }}, which has only been earned by {{ $recapData['rarestSoftcoreAchievementEarnRate'] }}% of players.
</x-mail::image-panel>
@endif

@if ($recapData['numForumPosts'] > 0 && $recapData['numComments'] > 0)
You made {{ $countThing($recapData['numForumPosts'], 'forum post') }} and {{ $countThing($recapData['numComments'], 'game comment') }}.
@elseif ($recapData['numForumPosts'] > 0)
You made {{ $countThing($recapData['numForumPosts'], 'forum post') }}.
@elseif ($recapData['numComments'] > 0)
You made {{ $countThing($recapData['numComments'], 'game comment') }}.
@endif

@if ($recapData['achievementsCreated'] > 0)
@if ($recapData['completedClaims'] > 0)
You published {{ $countThing($recapData['achievementsCreated'], 'new achievement') }} and {{ $countThing($recapData['completedClaims'], 'new set') }}.
@else
You published {{ $countThing($recapData['achievementsCreated'], 'new achievement') }}.
@endif
@endif

</x-mail::message>
