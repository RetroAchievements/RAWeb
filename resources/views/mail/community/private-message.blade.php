@use('App\Support\Shortcode\Shortcode')

@php
    $url = route('message-thread.show', ['messageThread' => $messageThread]);

    $payload = $message->body ?? '';
    $body = Shortcode::stripAndClamp($payload, 1850, preserveWhitespace: true);
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = preg_replace('/\n{3,}/', "\n\n", $body);
    $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
    $body = nl2br($body);
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
