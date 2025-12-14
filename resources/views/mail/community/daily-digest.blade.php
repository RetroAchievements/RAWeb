<x-mail::message>
Hello {{ $user->display_name }},

The following conversations that you have participated in were updated recently:

@foreach ($notificationItems as $notificationItem)
@php
    switch ($notificationItem['type']) {
        case 'ForumTopic':
            $clause = "in [${notificationItem['title']}](${notificationItem['link']}) (forum topic)";
            $postType = 'post';
            break;
        default:
            $lowerType = strtolower($notificationItem['type']);
            $clause = "on [${notificationItem['title']}](${notificationItem['link']}) ($lowerType)";
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

@endforeach

— Your friends at RetroAchievements.org
</x-mail::message>
