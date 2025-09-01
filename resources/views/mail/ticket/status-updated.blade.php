@php
    $ticketableType = $ticketable instanceof \App\Models\Achievement ? 'achievement' : 'leaderboard';
@endphp

<x-mail::message
    :categoryUrl="$categoryUrl"
    :categoryText="$categoryText"
>
Hello {{ $ticket->reporter->display_name }}!

The ticket for the following {{ $ticketableType }} has been updated.

<x-mail::panel>
**{{ ucfirst($ticketableType) }}:** {{ $ticketable->title }}  
**Game:** {{ $game->title }}  
**System:** {{ $game->system->name }}  
**Update:** {{ $comment }}
</x-mail::panel>

<x-mail::button :url="$ticketUrl">
View Ticket
</x-mail::button>

— Your friends at RetroAchievements.org
</x-mail::message>
