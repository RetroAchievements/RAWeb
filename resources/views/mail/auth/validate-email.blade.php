@php
    $url = config('app.url') . "/validateEmail.php?v={$validationToken}";
@endphp

<x-mail::message>
Hi {{ $user->display_name }},

Thank you for creating a RetroAchievements.org account. To finish setting up your account, please verify your email by clicking the link below:

<x-mail::button :url="$url">
Verify my email
</x-mail>

— Your friends at RetroAchievements.org
</x-mail::message>
