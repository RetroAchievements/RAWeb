@php
    $href = config('app.url') . "/resetPassword.php?u={$user->display_name}&t={$token}";
@endphp

<x-mail::message>
Hello {{ $user->display_name }},

We received a request to change your password on RetroAchievements.

[Click here to reset your password]({{ $href }}).

If you didn't make this request, you can ignore this email.

— Your friends at RetroAchievements.org
</x-mail::message>
