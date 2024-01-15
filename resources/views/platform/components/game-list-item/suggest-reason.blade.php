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
    @if ($relatedGameId > 0)
        Similar to mastered game
        <x-game.avatar
            :gameId="$relatedGameId"
            :gameTitle="$relatedGameTitle"
            :gameImageIcon="$relatedGameIcon"
            :iconSize="16"
        />
    @else
        In Similar Games collection
    @endif
@elseif ($selectionMethod == 'common-hub')
    Shares {{ $relatedSubject }}
    @if ($relatedGameId > 0)
        with mastered game
        <x-game.avatar
            :gameId="$relatedGameId"
            :gameTitle="$relatedGameTitle"
            :gameImageIcon="$relatedGameIcon"
            :iconSize="16"
        />
    @endif
@elseif ($selectionMethod == 'common-author')
    Shares set developer {!! userAvatar($relatedSubject, icon: false) !!}
    @if ($relatedGameId > 0)
        with mastered game
        <x-game.avatar
            :gameId="$relatedGameId"
            :gameTitle="$relatedGameTitle"
            :gameImageIcon="$relatedGameIcon"
            :iconSize="16"
        />
    @endif
@elseif ($selectionMethod == 'common-player')
    @if ($relatedGameId > 0)
        Also mastered by players of mastered game
        <x-game.avatar
            :gameId="$relatedGameId"
            :gameTitle="$relatedGameTitle"
            :gameImageIcon="$relatedGameIcon"
            :iconSize="16"
        />
    @else
        Also mastered by players of game
    @endif
@elseif ($selectionMethod == 'revised')
    Previously mastered game has been revised
@else
    Randomly selected
@endif
