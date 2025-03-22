@php
    $gameUrl = route('game.show', ['game' => $game]);
@endphp

<x-mail::message>
Hello {{ $user->display_name }},

An achievement set you have requested has received new achievements!

Check out the new achievements added to [{{ $game->title }}]({{ $gameUrl }}).

— Your friends at RetroAchievements.org
</x-mail::message>
