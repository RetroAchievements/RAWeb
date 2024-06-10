<?php
use App\Models\ForumTopic;
?>

@props([
    'game' => null, // Game
])

@php
    $doesGameHaveForumTopic = false;
    if ($game?->ForumTopicID) {
        $doesGameHaveForumTopic = ForumTopic::where('ID', $game->ForumTopicID)->exists();
    }
@endphp

@if ($doesGameHaveForumTopic)
    <p>
        No leaderboards found. Why not
        <a href="{{ route('forum.topic', ['forumTopic' => $game->ForumTopicID]) }}">
            suggest some
        </a>
        for this game?
    </p>
@else
    <p>No leaderboards found. Why not suggest some for this game?</p>
@endif
