<?php
use App\Models\ForumTopic;
?>

@props([
    'game' => null, // Game
])

@php
    $doesGameHaveForumTopic = false;
    if ($game?->forum_topic_id) {
        $doesGameHaveForumTopic = ForumTopic::where('id', $game->forum_topic_id)->exists();
    }
@endphp

@if ($doesGameHaveForumTopic)
    <p>
        No leaderboards found. Why not
        <a href="{{ route('forum-topic.show', ['topic' => $game->forum_topic_id]) }}">
            suggest some
        </a>
        for this game?
    </p>
@else
    <p>No leaderboards found. Why not suggest some for this game?</p>
@endif
