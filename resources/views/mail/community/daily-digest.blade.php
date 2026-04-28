<x-mail::message
    :categoryUrl="$categoryUrl"
    :categoryText="$categoryText"
>
Hello {{ $user->display_name }},

You have new updates on RetroAchievements:

@foreach ($notificationItems as $notificationItem)
@if ($notificationItem['type'] === 'GameScreenshotDecision')
@include('mail.community.partials.daily-digest-screenshot-decision', ['notificationItem' => $notificationItem])
@else
@php
    switch ($notificationItem['type']) {
        case 'ForumTopic':
            $clause = "in the forum topic [${notificationItem['title']}](${notificationItem['link']})";
            $postType = 'post';
            break;
        case 'AchievementTicket':
            $clause = "on the ticket for [${notificationItem['title']}](${notificationItem['link']})";
            $postType = 'comment';
            break;
        default:
            $lowerType = match ($notificationItem['type']) {
                'GameWall' => 'game',
                'UserWall' => 'user',
                default => strtolower($notificationItem['type']),
            };
            $clause = "on the {$lowerType} wall for [${notificationItem['title']}](${notificationItem['link']})";
            $postType = 'comment';
            break;
    }
@endphp

@if ($notificationItem['summary'] ?? null)
{{ $notificationItem['author']}} wrote {{ $clause }}:
<x-mail::panel>
{{ $notificationItem['summary'] }}
</x-mail::panel>
@else
{{ $notificationItem['count'] }} new {{ Str::plural($postType, $notificationItem['count']) }} {{ $clause }}.
@endif
@endif

@endforeach

— Your friends at RetroAchievements.org
</x-mail::message>
