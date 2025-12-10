<x-mail::message>
Hello {{ $user->display_name }},

The following conversations that you have participated in were updated recently:

@foreach ($notificationItems as $notificationItem)
@switch ($notificationItem['type'])

@case('ForumTopic')
* Forum Topic [{{ $notificationItem['title'] }}]({{ $notificationItem['link'] }}) ({{ $notificationItem['count'] }} new {{ Str::plural('post', $notificationItem['count']) }})
@break

@default
* {{ $notificationItem['type'] }} [{{ $notificationItem['title'] }}]({{ $notificationItem['link'] }}) ({{ $notificationItem['count'] }} new {{ Str::plural('comment', $notificationItem['count']) }})
@break

@endswitch
@endforeach

— Your friends at RetroAchievements.org
</x-mail::message>
