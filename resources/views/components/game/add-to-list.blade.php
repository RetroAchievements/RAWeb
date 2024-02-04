@props([
    'user' => null,
    'gameId' => 0,
    'type' => 'play',
])

<?php

use App\Enums\Permissions;

$addButtonTooltip = __('user-game-list.' . $type . '.add');
$removeButtonTooltip = __('user-game-list.' . $type . '.remove');
$addVisibility = '';
$removeVisibility = '';
$onLists = !empty($user) ? getUserGameListsContaining($user, $gameId) : [];

$buttonTooltip = '';
if (in_array($type, $onLists)) {
    $addVisibility = 'hidden';
    $buttonTooltip = $removeButtonTooltip;
} else {
    $removeVisibility = 'hidden';
    $buttonTooltip = $addButtonTooltip;
}
?>

@if (!empty($user))
    <button id='{{ $type }}-list-button' class='btn' type='button' title='{{ $buttonTooltip }}'
            onClick="toggleListItem('{{ $type }}')">
        <div class="flex items-center gap-x-1">
            <div id='add-to-{{ $type }}-list' class="{{ $addVisibility }}">
                <x-fas-plus class="-mt-0.5 w-[12px] h-[12px]"/>
            </div>
            <div id='remove-from-{{ $type }}-list' class="{{ $removeVisibility }}">
                <x-fas-check class="-mt-0.5 w-[12px] h-[12px]"/>
            </div>
            <span>{{ __('user-game-list.' . $type . '.name') }}</span>
        </div>
    </button>

    <script>
    function toggleListItem(type) {
        $.post('/request/user-game-list/toggle.php', {
            type: type,
            game: {{ $gameId }}
        })
            .done(function () {
                $("#add-to-" + type + "-list").toggle();
                $("#remove-from-" + type + "-list").toggle();
                if ($("#add-to-" + type + "-list").is(':visible')) {
                    $("#" + type + "-list-button").prop('title', '{{ $addButtonTooltip }}');
                } else {
                    $("#" + type + "-list-button").prop('title', '{{ $removeButtonTooltip }}');
                }
            });
    }
</script>
@endif
