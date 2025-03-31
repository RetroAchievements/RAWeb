<x-mail::message>
Hello,

Great news! Your username change request to {{ $newDisplayName }} has been approved.

You can now use your new username to log in everywhere on [RetroAchievements.org](https://retroachievements.org).

Check out your updated profile [here]({{ route('user.show', ['user' => $newDisplayName]) }}).

— Your friends at RetroAchievements.org
</x-mail::message>
