@props([
    'selectionMethod' => 'random',
    'relatedSubject' => '',
    'relatedGameId' => 0,
    'relatedGameTitle' => '',
    'relatedGameIcon' => '',
])

@if ($selectionMethod == 'want-to-play')
    From your Want to Play list
@elseif ($selectionMethod == 'similar-to')
    Similar to mastered game
    <x-game.avatar
        :gameId="$relatedGameId"
        :gameTitle="$relatedGameTitle"
        :gameImageIcon="$relatedGameIcon"
        :iconSize="16"
    />
@elseif ($selectionMethod == 'common-hub')
    Shares {{ $relatedSubject }} with mastered game
    <x-game.avatar
        :gameId="$relatedGameId"
        :gameTitle="$relatedGameTitle"
        :gameImageIcon="$relatedGameIcon"
        :iconSize="16"
    />
@elseif ($selectionMethod == 'common-author')
    Shares set developer {{ $relatedSubject }} with mastered game
    <x-game.avatar
        :gameId="$relatedGameId"
        :gameTitle="$relatedGameTitle"
        :gameImageIcon="$relatedGameIcon"
        :iconSize="16"
    />
@elseif ($selectionMethod == 'common-player')
    Also mastered by players of mastered game
    <x-game.avatar
        :gameId="$relatedGameId"
        :gameTitle="$relatedGameTitle"
        :gameImageIcon="$relatedGameIcon"
        :iconSize="16"
    />
@elseif ($selectionMethod == 'revised')
    Previously mastered game has been revised
@else
    Randomly selected
@endif
