@use('App\Support\Shortcode\Shortcode')

@php
    $body = '';
    if (!empty($payload)) {
        $body = $payload;
        $body = Shortcode::stripAndClamp($body, 1850, preserveWhitespace: true);
        $body = str_replace(["\r\n", "\r"], "\n", $body); // Convert to Unix newlines.
        $body = preg_replace('/\n{3,}|(<br\s*\/?>\s*){3,}/i', "\n\n", $body);
    }

    $url = $urlTarget;
    if (!str_starts_with($url, 'http')) {
        $url = config('app.url') . "/{$url}";
    }
@endphp

<x-mail::message>
Hello {{ $toUserDisplayName }}!

{{ $activityCommenterDisplayName }} has commented on {{ $activityDescription }}.

@if (!empty($body))
<x-mail::panel>
{!! $body !!}
</x-mail::panel>
@endif

<x-mail::button :url="$url">
View post
</x-mail::button>

— Your friends at RetroAchievements.org
</x-mail::message>
