<?php

use App\Community\Enums\UserRelationship;
?>

@props([
    'user' => null, // User
])

@php
    $me = auth()->user() ?? null;
    $myFriendshipType = $me ? $me->getRelationship($user) : UserRelationship::NotFollowing;
@endphp

@if ($me && $me->User !== $user->User)
    <div class="flex items-center gap-x-1">
        @if ($myFriendshipType !== UserRelationship::Blocked)
            <form action="/request/user/update-relationship.php" method="POST">
                {!! csrf_field() !!}
                <input type="hidden" name="user" value="{{ $user->User }}">
                <input type="hidden" name="action" value="{{ UserRelationship::Blocked }}">
                <button class="btn btn-link">Block</button>
            </form>
        @else
            <form action="/request/user/update-relationship.php" method="POST">
                {!! csrf_field() !!}
                <input type="hidden" name="user" value="{{ $user->User }}">
                <input type="hidden" name="action" value="{{ UserRelationship::NotFollowing }}">
                <button class="btn btn-link">Unblock</button>
            </form>
        @endif

        <a class="btn max-h-[24px] flex items-center" href="{{ route('message.create', ['to' => $user->User]) }}">
            <x-fas-envelope />
            <span class="sr-only">Message</span>
        </a>

        @if ($myFriendshipType === UserRelationship::Following)
            <form action="/request/user/update-relationship.php" method="POST">
                {!! csrf_field() !!}
                <input type="hidden" name="user" value="{{ $user->User }}">
                <input type="hidden" name="action" value="{{ UserRelationship::NotFollowing }}">
                <button class="btn btn max-h-[24px] flex items-center">Unfollow</button>
            </form>
        @elseif ($myFriendshipType === UserRelationship::NotFollowing)
            <form action="/request/user/update-relationship.php" method="POST">
                {!! csrf_field() !!}
                <input type="hidden" name="user" value="{{ $user->User }}">
                <input type="hidden" name="action" value="{{ UserRelationship::Following }}">
                <button class="btn max-h-[24px] flex items-center">
                    Follow
                </button>
            </form>
        @endif
    </div>
@endif
