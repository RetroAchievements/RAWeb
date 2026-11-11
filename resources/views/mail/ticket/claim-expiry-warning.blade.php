@php
    $ticketableKind = $ticketable instanceof \App\Models\Achievement ? 'achievement' : 'leaderboard';
@endphp

<x-mail::message
    :categoryUrl="$categoryUrl"
    :categoryText="$categoryText"
>
Hello {{ $ticket->assignee->display_name }}!

You claimed a ticket for this {{ $ticketableKind }}, but there has been no activity from you on it for a while.

**{{ ucfirst($ticketableKind) }}:** {{ $ticketable->title }}  
**Game:** {{ $game->title }}  
**System:** {{ $game->system->name }}

If you don't comment on the ticket, change its status, or save new logic for the {{ $ticketableKind }} by **{{ $expiresAt->format('F j, Y') }}**, your claim will expire and the ticket will go back to the maintainer. If you no longer plan to work on it, you can release your claim on the ticket page.

<x-mail::button :url="$ticketUrl">
View Ticket
</x-mail::button>

— Your friends at RetroAchievements.org
</x-mail::message>
