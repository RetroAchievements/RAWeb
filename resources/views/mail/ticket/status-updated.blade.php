@php
    $ticketableType = $ticketable instanceof \App\Models\Achievement ? 'achievement' : 'leaderboard';
@endphp

<x-mail::message
    :categoryUrl="$categoryUrl"
    :categoryText="$categoryText"
>
Hello {{ $ticket->reporter->display_name }}!

The ticket for the following {{ $ticketableType }} has been updated.

**{{ ucfirst($ticketableType) }}:** {{ $ticketable->title }}  
**Game:** {{ $game->title }}  
**System:** {{ $game->system->name }}

<x-mail::panel>
{{ $comment }}
</x-mail::panel>

<x-mail::button :url="$ticketUrl">
View ticket
</x-mail::button>

— Your friends at RetroAchievements.org
</x-mail::message>
