@php
    $ticketableKind = $ticketable instanceof \App\Models\Achievement ? 'achievement' : 'leaderboard';
@endphp

<x-mail::message
    :categoryUrl="$categoryUrl"
    :categoryText="$categoryText"
>
Hello {{ $maintainer->display_name }}!

@switch($change)
@case(\App\Platform\Enums\TicketChangelogEntryType::Claimed)
{{ $claimant->display_name }} claimed a ticket on one of your {{ $ticketableKind }}s. They've volunteered to handle the ticket for now.
@break
@case(\App\Platform\Enums\TicketChangelogEntryType::ClaimExpired)
{{ $claimant->display_name }}'s claim on a ticket for one of your {{ $ticketableKind }}s expired. The ticket was reassigned back to you.
@break
@default
{{ $claimant->display_name }} released their claim on a ticket for one of your {{ $ticketableKind }}s. The ticket was reassigned back to you.
@endswitch

**{{ ucfirst($ticketableKind) }}:** {{ $ticketable->title }}  
**Game:** {{ $game->title }}  
**System:** {{ $game->system->name }}

<x-mail::button :url="$ticketUrl">
View Ticket
</x-mail::button>

— Your friends at RetroAchievements.org
</x-mail::message>
