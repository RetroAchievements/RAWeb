@use('App\Support\Shortcode\Shortcode')

@php
    $url = route('message-thread.show', ['messageThread' => $messageThread]);

    $body = $message->body ?? '';
    if (!empty($body)) {
        $body = Shortcode::stripAndClamp($body, 1850, preserveWhitespace: true);
        $body = str_replace(["\r\n", "\r"], "\n", $body); // Convert to Unix newlines.
        $body = preg_replace('/\n{3,}|(<br\s*\/?>\s*){3,}/i', "\n\n", $body);
        $body = nl2br($body);
    }
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
