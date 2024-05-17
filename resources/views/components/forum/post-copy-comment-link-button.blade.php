@props([
    'forumTopicComment' => null, // ForumTopicComment
    'threadPostNumber' => 1,
])

<?php
$commentId = $forumTopicComment->id;
$forumTopicId = $forumTopicComment->forum_topic_id;

$postCommentUrl = config('app.url') . "/viewtopic.php?t=$forumTopicId&c=$commentId#$commentId";
?>

<button
    class='btn p-1 absolute lg:static text-xs top-1 right-1 flex items-center gap-x-1'
    onclick='copyToClipboard("{{ $postCommentUrl }}"); showStatusSuccess("Copied")'
    aria-label="Copy post number {{ $threadPostNumber }}"
>
    {{-- TODO: Re-enable this once unauthorized comments are filtered at the query level. --}}
    {{-- #{{ $threadPostNumber }} --}}
    <x-fas-link class='w-3 h-3' />
</button>
