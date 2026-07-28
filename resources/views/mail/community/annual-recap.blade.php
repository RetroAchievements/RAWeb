@php

$countThing = function (?int $count, string $thing) {
    if ($count) {
        return $count . ' ' . Str::plural($thing, $count);
    } else {
        return '';
    }
};

$hardcorePointsClause = $countThing($recapData['hardcorePointsEarned'], 'hardcore point');
$casualPointsClause = $countThing($recapData['casualPointsEarned'], 'casual point');

if ($hardcorePointsClause && $casualPointsClause) {
    $casualPointsClause = ' and ' . $casualPointsClause;
}

$achievementsClause = '';
if ($recapData['achievementsUnlocked'] > 0) {
    $achievementsClause = ' and unlocked ' . $countThing($recapData['achievementsUnlocked'], 'achievement') . ', earning you ' . $hardcorePointsClause . $casualPointsClause;
}

$subsetHardcorePointsClause = $countThing($recapData['subsetHardcorePointsEarned'], 'hardcore point');
$subsetCasualPointsClause = $countThing($recapData['subsetCasualPointsEarned'], 'casual point');

if ($subsetHardcorePointsClause && $subsetCasualPointsClause) {
    $subsetCasualPointsClause = ' and ' . $subsetCasualPointsClause;
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
        $completionClause = "You completed $numCompletions, and beat an additional $numBeaten on casual.";
    } else {
        $completionClause = "You completed $numCompletions.";
    }
} elseif ($numBeaten) {
    $completionClause = "You beat $numBeaten on casual.";
}

@endphp

<x-mail::message>
Congratulations {{ $user->display_name }}!

In {{ $recapData['year'] }}, you played {{ $countThing($recapData['gamesPlayed'], 'game') }} on <a href="{{ route('home') }}">retroachievements.org</a>{{ $achievementsClause }}. {{ $leaderboardsSubmittedClause }}

You spent {{ $recapData['totalPlaytime'] }} playing games {{ $playedSystemsClause }}.

{{ $masteryClause }}{{ $completionClause }}

@if ($recapData['mostPlayedGame'])
<x-mail::image-panel
    src="{{ media_asset($recapData['mostPlayedGame']->image_icon_asset_path) }}"
    alt="{{ $recapData['mostPlayedGame']->title }} game badge"
    url="{{ route('game.show', $recapData['mostPlayedGame']) }}"
    width="64"
    height="64"
>
    Your most played game was <a href="{{ route('game.show', $recapData['mostPlayedGame']) }}">{{ $recapData['mostPlayedGame']->title }}</a> at {{ $recapData['mostPlayedGamePlaytime'] }}.
</x-mail::image-panel>
@endif

@if ($recapData['rarestHardcoreAchievement'])
<x-mail::image-panel
    src="{{ $recapData['rarestHardcoreAchievement']->badge_unlocked_url }}"
    alt="{{ $recapData['rarestHardcoreAchievement']->title }} achievement badge"
    url="{{ route('achievement.show', $recapData['rarestHardcoreAchievement']) }}"
    width="64"
    height="64"
>
    Your rarest achievement earned was <a href="{{ route('achievement.show', $recapData['rarestHardcoreAchievement']) }}">{{ $recapData['rarestHardcoreAchievement']->title }}</a> from {{ $recapData['rarestHardcoreAchievement']->game->title }}, which has only been earned in hardcore by {{ $recapData['rarestHardcoreAchievementEarnRate'] }}% of players.
</x-mail::image-panel>
@elseif ($recapData['rarestCasualAchievement'])
<x-mail::image-panel
    src="{{ $recapData['rarestCasualAchievement']->badge_unlocked_url }}"
    alt="{{ $recapData['rarestCasualAchievement']->title }} achievement badge"
    url="{{ route('achievement.show', $recapData['rarestCasualAchievement']) }}"
    width="64"
    height="64"
>
    Your rarest achievement earned was <a href="{{ route('achievement.show', $recapData['rarestCasualAchievement']) }}">{{ $recapData['rarestCasualAchievement']->title }}</a> from {{ $recapData['rarestCasualAchievement']->game->title }}, which has only been earned by {{ $recapData['rarestCasualAchievementEarnRate'] }}% of players.
</x-mail::image-panel>
@endif

@if ($recapData['subsetAchievementsUnlocked'])
You also unlocked {{ $countThing($recapData['subsetAchievementsUnlocked'], 'subset achievement') }}, earning you an additional {{ $subsetHardcorePointsClause }}{{ $subsetCasualPointsClause }}.
@if ($recapData['rarestSubsetHardcoreAchievement'])
<x-mail::image-panel
    src="{{ $recapData['rarestSubsetHardcoreAchievement']->badge_unlocked_url }}"
    alt="{{ $recapData['rarestSubsetHardcoreAchievement']->title }} achievement badge"
    url="{{ route('achievement.show', $recapData['rarestSubsetHardcoreAchievement']) }}"
    width="64"
    height="64"
>
    Your rarest subset achievement earned was <a href="{{ route('achievement.show', $recapData['rarestSubsetHardcoreAchievement']) }}">{{ $recapData['rarestSubsetHardcoreAchievement']->title }}</a> from {{ $recapData['rarestSubsetHardcoreAchievement']->game->title }}, which has only been earned in hardcore by {{ $recapData['rarestSubsetHardcoreAchievementEarnRate'] }}% of players.
</x-mail::image-panel>
@elseif ($recapData['rarestSubsetCasualAchievement'])
<x-mail::image-panel
    src="{{ $recapData['rarestSubsetCasualAchievement']->badge_unlocked_url }}"
    alt="{{ $recapData['rarestSubsetCasualAchievement']->title }} achievement badge"
    url="{{ route('achievement.show', $recapData['rarestSubsetCasualAchievement']) }}"
    width="64"
    height="64"
>
    Your rarest subset achievement earned was <a href="{{ route('achievement.show', $recapData['rarestSubsetCasualAchievement']) }}">{{ $recapData['rarestSubsetCasualAchievement']->title }}</a> from {{ $recapData['rarestSubsetCasualAchievement']->game->title }}, which has only been earned by {{ $recapData['rarestSubsetCasualAchievementEarnRate'] }}% of players.
</x-mail::image-panel>
@endif
@endif


@if ($recapData['numEventAwards'] > 0 && $recapData['numSiteAwards'] > 0)
You were awarded badges from {{ $countThing($recapData['numEventAwards'], 'event') }}. You also earned {{ $countThing($recapData['numSiteAwards'], 'site award') }}.
@elseif ($recapData['numEventAwards'] > 0)
You were awarded badges from {{ $countThing($recapData['numEventAwards'], 'event') }}.
@elseif ($recapData['numSiteAwards'] > 0)
You earned {{ $countThing($recapData['numSiteAwards'], 'site award') }}.
@endif

@if ($recapData['numForumPosts'] > 0 && $recapData['numComments'] > 0)
You made {{ $countThing($recapData['numForumPosts'], 'forum post') }} and {{ $countThing($recapData['numComments'], 'game comment') }}.
@elseif ($recapData['numForumPosts'] > 0)
You made {{ $countThing($recapData['numForumPosts'], 'forum post') }}.
@elseif ($recapData['numComments'] > 0)
You made {{ $countThing($recapData['numComments'], 'game comment') }}.
@endif

@if ($recapData['developmentTime'])
@if ($recapData['achievementsCreated'] === 0)
You spent {{ $recapData['developmentTime'] }} developing sets.
@elseif ($recapData['completedClaims'] > 0)
You spent {{ $recapData['developmentTime'] }} developing sets. You published {{ $countThing($recapData['achievementsCreated'], 'new achievement') }} and {{ $countThing($recapData['completedClaims'], 'new set') }}.
@else
You spent {{ $recapData['developmentTime'] }} developing sets. You published {{ $countThing($recapData['achievementsCreated'], 'new achievement') }}.
@endif
@endif

</x-mail::message>
