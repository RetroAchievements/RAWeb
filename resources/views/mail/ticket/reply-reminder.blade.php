@use('App\Support\Shortcode\Shortcode')

@php
    $ticketableKind = $ticketable instanceof \App\Models\Achievement ? 'achievement' : 'leaderboard';

    $quotedReply = $question?->body ?? '';
    if (!empty($quotedReply)) {
        $quotedReply = Shortcode::prepareForMailPanel($quotedReply);
    }
@endphp

<x-mail::message
    :categoryUrl="$categoryUrl"
    :categoryText="$categoryText"
>
Hello {{ $ticket->reporter->display_name }}!

A developer is waiting for your reply on your ticket for this {{ $ticketableKind }}.

**{{ ucfirst($ticketableKind) }}:** {{ $ticketable->title }}  
**Game:** {{ $game->title }}  
**System:** {{ $game->system->name }}

@if (!empty($quotedReply))
{{ $question->user->display_name }} wrote:

<x-mail::panel>
{!! $quotedReply !!}
</x-mail::panel>
@endif

If there is no reply by **{{ $closeAt->format('F j, Y') }}**, we will close this ticket. You can still reply after that. Your reply will reopen the ticket.

<x-mail::button :url="$replyUrl">
Reply
</x-mail::button>

Is the {{ $ticketableKind }} working for you now? [Close your ticket]({{ $noLongerAProblemUrl }})

— Your friends at RetroAchievements.org
</x-mail::message>
