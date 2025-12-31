<?php

use App\Community\Enums\UserRelationStatus;
?>

@props([
    'user' => null, // User
])

@php
    $me = auth()->user() ?? null;
    $myRelationStatus = $me ? $me->getRelationship($user) : UserRelationStatus::NotFollowing;
@endphp

@if ($me && $me->username !== $user->username)
    <div class="flex items-center gap-x-1">
        @if ($myRelationStatus !== UserRelationStatus::Blocked)
            <form action="/request/user/update-relationship.php" method="POST">
                {!! csrf_field() !!}
                <input type="hidden" name="user" value="{{ $user->display_name }}">
                <input type="hidden" name="action" value="{{ UserRelationStatus::Blocked->value }}">
                <button class="btn btn-link">Block</button>
            </form>
        @else
            <form action="/request/user/update-relationship.php" method="POST">
                {!! csrf_field() !!}
                <input type="hidden" name="user" value="{{ $user->display_name }}">
                <input type="hidden" name="action" value="{{ UserRelationStatus::NotFollowing->value }}">
                <button class="btn btn-link">Unblock</button>
            </form>
        @endif

        <a class="btn max-h-[24px] flex items-center" href="{{ route('message-thread.create', ['to' => $user->display_name]) }}">
            <x-fas-envelope />
            <span class="sr-only">Message</span>
        </a>

        @if ($myRelationStatus === UserRelationStatus::Following)
            <form action="/request/user/update-relationship.php" method="POST">
                {!! csrf_field() !!}
                <input type="hidden" name="user" value="{{ $user->display_name }}">
                <input type="hidden" name="action" value="{{ UserRelationStatus::NotFollowing->value }}">
                <button class="btn btn max-h-[24px] flex items-center">Unfollow</button>
            </form>
        @elseif ($myRelationStatus === UserRelationStatus::NotFollowing && !$me->isFreshAccount())
            <form action="/request/user/update-relationship.php" method="POST">
                {!! csrf_field() !!}
                <input type="hidden" name="user" value="{{ $user->display_name }}">
                <input type="hidden" name="action" value="{{ UserRelationStatus::Following->value }}">
                <button class="btn max-h-[24px] flex items-center">
                    Follow
                </button>
            </form>
        @endif
    </div>
@endif
