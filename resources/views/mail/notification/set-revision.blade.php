@php
    $gameUrl = route('game.show', ['game' => $game]);
@endphp

<x-mail::message>
Hello {{ $user->display_name }},

The achievement set for [{{ $game->title }}]({{ $gameUrl }}) has been updated.

**Your {{ $isHardcore ? 'mastery' : 'completion' }} status is safe.** You've already earned your badge, and it isn't going anywhere.

If you're curious about what changed or want an excuse to revisit this retro game, [check out its game page]({{ $gameUrl }}). You've already {{ $isHardcore ? 'mastered' : 'completed' }} it - this may be a great chance to earn even more points.

— Your friends at RetroAchievements.org
</x-mail::message>
