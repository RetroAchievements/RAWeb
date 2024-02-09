@props([
    'gameId' => 0,
    'isOnBacklog' => false,
])

<?php
$addVisibility = '';
$removeVisibility = '';
if ($isOnBacklog) {
    $addVisibility = ' class="hidden"';
    $buttonTooltip = __('user-game-list.play.remove');
} else {
    $removeVisibility = ' class="hidden"';
    $buttonTooltip = __('user-game-list.play.add');
}
?>
<button id="play-list-button-{{ $gameId }}" class="btn" type="button"'
        title="{{ $buttonTooltip }}"
        onClick="togglePlayListItem({{ $gameId }})">
    <div class="flex items-center gap-x-1">
        <div id="add-to-list-{{ $gameId }}"{!! $addVisibility !!}>
            <x-fas-plus class="-mt-0.5 w-[12px] h-[12px]" />
        </div>
        <div id="remove-from-list-{{ $gameId }}"{!! $removeVisibility !!}>
            <x-fas-check class="-mt-0.5 w-[12px] h-[12px]" />
        </div>
    </div>
</button>
