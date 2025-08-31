@php
    $ticketableType = $ticketable instanceof \App\Models\Achievement ? 'achievement' : 'leaderboard';
@endphp

<x-mail::message>
Hello {{ $ticket->reporter->display_name }}!

@if ($ticket->reporter->is($updatedBy))
The ticket you opened for the following {{ $ticketableType }} had its status changed to *{{ $newStatus }}*.
@else
The ticket you opened for the following {{ $ticketableType }} had its status changed to *{{ $newStatus }}* by *{{ $updatedBy->display_name }}*.
@endif

<x-mail::panel>
**{{ ucfirst($ticketableType) }}:** {{ $ticketable->title }}  
**Game:** {{ $game->title }}  
**System:** {{ $game->system->name }}  

**Status Update:** {{ $comment }}
</x-mail::panel>

<x-mail::button :url="$ticketUrl">
View Ticket
</x-mail::button>

— Your friends at RetroAchievements.org
</x-mail::message>