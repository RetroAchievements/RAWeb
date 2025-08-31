@php
    $ticketableType = $ticketable instanceof \App\Models\Achievement ? 'achievement' : 'leaderboard';
@endphp

<x-mail::message
    :granularUrl="$granularUrl"
    :granularText="$granularText"
    :categoryUrl="$categoryUrl"
    :categoryText="$categoryText"
>
Hello {{ $user->display_name }}!

@if ($isMaintainer)
{{ $ticket->reporter->display_name }} would like to report a bug with {{ $ticketableType === 'achievement' ? 'an achievement' : 'a leaderboard' }} you've created:
@else
{{ $ticket->reporter->display_name }} would like to report a bug with {{ $ticketableType === 'achievement' ? 'an achievement' : 'a leaderboard' }} you're subscribed to:
@endif

<x-mail::panel>
**{{ ucfirst($ticketableType) }}:** {{ $ticketable->title }}  
**Game:** {{ $game->title }}  
**System:** {{ $game->system->name }}  
**Problem:** {{ $problemType }}  

@if ($ticket->body)
**Comment:**  
{{ $ticket->body }}
@endif
</x-mail::panel>

This ticket will be raised and will be available for all developers to inspect and manage.

<x-mail::button :url="$ticketUrl">
View Ticket
</x-mail::button>

Thanks!

— Your friends at RetroAchievements.org
</x-mail::message>
