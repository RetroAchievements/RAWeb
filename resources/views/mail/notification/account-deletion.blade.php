@php
    $deleteDate = getDeleteDate($user->DeleteRequested);
@endphp

<x-mail::message>
Hello {{ $user->display_name }},

Your account has been marked for deletion.

If you do not cancel this request before {{ $deleteDate }}, you will no longer be able to access your RetroAchievements.org account.

<x-mail::button :url="route('settings.show')">
Cancel Deletion Request
</x-mail>

— Your friends at RetroAchievements.org
</x-mail::message>
