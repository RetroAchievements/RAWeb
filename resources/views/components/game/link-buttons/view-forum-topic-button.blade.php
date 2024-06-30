@props([
    'forumTopicId' => null, // ?int
])

<x-game.link-buttons.game-link-button
    icon="💬"
    href="{{ route('forum.topic', ['forumTopic' => $forumTopicId]) }}"
>
    Official Forum Topic
</x-game.link-buttons.game-link-button>
