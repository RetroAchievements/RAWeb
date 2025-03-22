@php
    $gameUrl = route('game.show', ['game' => $game]);
@endphp

<x-mail::message>
Hello {{ $user->display_name }},

An achievement set you have previously {{ $isHardcore ? 'mastered' : 'completed' }} has been revised.

Check out the changes to [{{ $game->title }}]({{ $gameUrl }}).

— Your friends at RetroAchievements.org
</x-mail::message>
