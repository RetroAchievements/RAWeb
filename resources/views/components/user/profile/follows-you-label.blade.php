<?php

use App\Community\Enums\UserRelationship;
use Illuminate\Support\Facades\Auth;
?>

@props([
    'user' => null, // User
])

@php
    $me = Auth::user() ?? null;
    $areTheyFollowingMe = $me ? $user->isFollowing($me) : false;
@endphp

@if ($areTheyFollowingMe)
    <div class="h-[24px] max-w-fit flex justify-center items-center bg-embed px-1 py-0.5 border border-embed-highlight rounded">
        <p class="-mt-px">Follows you</p>
    </div>
@endif
