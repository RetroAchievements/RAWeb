@use('App\Support\Shortcode\Shortcode')

@php
    $url = route('message-thread.show', ['messageThread' => $messageThread, 'message' => $message->id]);

    $body = $message->body ?? '';
    if (!empty($body)) {
        $body = Shortcode::prepareForMailPanel($body);
    }
@endphp

<x-mail::message
    :categoryUrl="$categoryUrl"
    :categoryText="$categoryText"
>
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
