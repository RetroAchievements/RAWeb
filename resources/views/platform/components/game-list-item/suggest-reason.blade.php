@props([
    'selectionMethod' => 'random',
    'relatedSubject' => '',
    'relatedGameId' => 0,
    'relatedGameType' => '',
    'relatedGameTitle' => '',
    'relatedGameIcon' => '',
])

@if ($selectionMethod == 'want-to-play')
    From your Want to Play list
@elseif ($selectionMethod == 'similar-to')
    @if ($relatedGameId > 0)
        Similar to {{ $relatedGameType }} game:
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
    Shares hub {{ $relatedSubject }}
    @if ($relatedGameId > 0)
        with {{ $relatedGameType }} game:
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
        with {{ $relatedGameType }} game:
        <x-game.avatar
            :gameId="$relatedGameId"
            :gameTitle="$relatedGameTitle"
            :gameImageIcon="$relatedGameIcon"
            :iconSize="16"
        />
    @endif
@elseif ($selectionMethod == 'common-player')
    @if ($relatedGameId > 0)
        Mastered by players of {{ $relatedGameType }} game:
        <x-game.avatar
            :gameId="$relatedGameId"
            :gameTitle="$relatedGameTitle"
            :gameImageIcon="$relatedGameIcon"
            :iconSize="16"
        />
    @else
        Mastered by players of game
    @endif
@elseif ($selectionMethod == 'revised')
    Previously mastered game has been revised
@else
    Randomly selected
@endif
