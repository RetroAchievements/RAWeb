<x-mail::message>
Hello {{ $toUser->display_name }},

{{ $fromUser->display_name }} has started following you!

<x-mail::button :url="route('user.show', ['user' => $fromUser])">
View their profile
</x-mail::button>

— Your friends at RetroAchievements.org
</x-mail::message>
