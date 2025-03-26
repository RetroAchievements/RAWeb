@use('App\Support\Shortcode\Shortcode')

@php
    $url = route('message-thread.show', ['messageThread' => $messageThread]);
    
    $payload = $message->body ?? '';
    $body = nl2br(Shortcode::stripAndClamp($payload, 1850, preserveWhitespace: true));
    $body = str_replace(["\r\n", "\r"], "\n", $body); // Convert to Unix newlines.
    $body = preg_replace('/\n{3,}|(<br\s*\/?>\s*){3,}/i', "\n\n", $body); // Handle both \n and <br>.
    $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
@endphp

<x-mail::message>
Hello {{ $userTo->display_name }},

You have received a new private message from {{ $userFrom->display_name }}.

Subject: *{{ $messageThread->title }}*

<x-mail::panel>
{!! $body !!}
</x-mail::panel>

<x-mail::button :url="$url">
Reply
</x-mail::button>

— Your friends at RetroAchievements.org
</x-mail::message>
