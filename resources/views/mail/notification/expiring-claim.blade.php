@use('Illuminate\Support\Carbon')
@use('Illuminate\Support\Str')

@php
    $gameUrl = route('game.show', ['game' => $claim->game]);
    $claimType = Str::lower($claim->claim_type->label());
    $expireTime = $claim->finished_at->diffForHumans(Carbon::now(), ['syntax' => Carbon::DIFF_RELATIVE_TO_NOW]);
@endphp

<x-mail::message>
Hello {{ $claim->user->display_name }},

Your {{ $claimType }} claim on [{{ $claim->game->title }}]({{ $gameUrl }}) expires {{ $expireTime }}.

— Your friends at RetroAchievements.org
</x-mail::message>
