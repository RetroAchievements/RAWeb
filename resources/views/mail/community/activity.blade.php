@use('App\Support\Shortcode\Shortcode')

@php
    $body = '';
    if (!empty($payload)) {
        $body = Shortcode::prepareForMailPanel($payload);
    }

    $url = $urlTarget;
    if (!str_starts_with($url, 'http')) {
        $url = config('app.url') . "/{$url}";
    }
@endphp

<x-mail::message
    :granularUrl="$granularUrl"
    :granularText="$granularText"
    :categoryUrl="$categoryUrl"
    :categoryText="$categoryText"
>
Hello {{ $toUserDisplayName }}!

{{ $activityCommenterDisplayName }} has commented on {{ $activityDescription }}.

@if ($commentableType === \App\Community\Enums\CommentableType::Ticket && isset($ticketable) && isset($game))
@php
    $ticketableType = $ticketable instanceof \App\Models\Achievement ? 'achievement' : 'leaderboard';
@endphp

**{{ ucfirst($ticketableType) }}:** {{ $ticketable->title }}  
**Game:** {{ $game->title }}  
**System:** {{ $game->system->name }}
@endif

@if (!empty($body))
<x-mail::panel>
{!! $body !!}
</x-mail::panel>
@endif

<x-mail::button :url="$url">
@if ($commentableType === \App\Community\Enums\CommentableType::Forum)
View post
@else
View comment
@endif
</x-mail::button>

— Your friends at RetroAchievements.org
</x-mail::message>
