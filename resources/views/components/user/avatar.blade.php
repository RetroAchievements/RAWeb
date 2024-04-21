<?php

use App\Models\User;

/* @var User $user */
$user ??= $model ?? null;

$class ??= '';
$display ??= 'name';
$iconSize ??= 'sm';
$link ??= $user->canonicalUrl ?? null;
$tooltip ??= true;

$iconWidth = config('media.icon.' . $iconSize . '.width');
$iconHeight = config('media.icon.' . $iconSize . '.height');
?>

{{--
    TODO $display should be an array, allowing the developer to 
    render both the icon and username if they choose.
--}}

<x-avatar
    :class="$class"
    :display="$display"
    :link="$link"
    :model="$user"
    resource="user"
    :tooltip="$tooltip"
>
    @if($user ?? false)
        @if($display === 'icon')
            <img src="{{ asset($user->avatarUrl) }}" class="icon-{{ $iconSize }} {{ $class }}" loading="lazy"
                 width="{{ $iconWidth }}" height="{{ $iconHeight }}" alt="{{ $user->display_name }}">
        @endif
        @if($display === 'id'){{ $user->id }}@endif
        @if($display === 'name'){{ $user->display_name ?? $user->username }}@endif
    @endif
</x-avatar>
