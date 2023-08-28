@props([
    'consoleName' => 'Unknown Console',
    'gameTitle' => 'Unknown Game',
    'gameID' => 0,
    'iconUrl' => asset("assets/images/system/unknown.png"),
    'user' => null,
])

<?php
use App\Community\Enums\UserGameListType;

// TODO: Migrate renderGameTitle to a Blade component.
$renderedTitle = renderGameTitle($gameTitle);

$addButtonTooltip = 'Add to Want to Play list';
$removeButtonTooltip = 'Remove from Want to Play list';
$addVisibility = '';
$removeVisibility = '';
$onLists = !empty($user) ? getUserGameListsContaining($user, $gameID) : [];

$buttonTooltip = '';
if (in_array(UserGameListType::Play, $onLists)) {
    $addVisibility = 'hidden';
    $buttonTooltip = $removeButtonTooltip;
} else {
    $removeVisibility = 'hidden';
    $buttonTooltip = $addButtonTooltip;
}
?>

<h1 class="text-h3">
    <span class="block mb-1">{!! $renderedTitle !!}</span>

    @if (!empty($user))
    <div class="md:float-right">
        <button id='play-list-button' class='btn' type='button' title='{{ $buttonTooltip }}'
                onClick="toggleListItem('{{ UserGameListType::Play}}')">
            <div class="flex items-center gap-x-1">
                <div id='add-to-play-list' class="md:float-left w-[18px] h-[18px] {{ $addVisibility }}">
                    <x-icon.add-to-list />
                </div>
                <div id='remove-from-play-list' class="w-[18px] h-[18px] {{ $removeVisibility }}">
                    <x-icon.remove-from-list />
                </div>
                <span class="block text-sm tracking-tighter">Want to Play</span>
            </div>
        </button>
    </div>
    @endif

    <div class="flex items-center gap-x-1">
        <img src="{{ $iconUrl }}" width="24" height="24" alt="Console icon">
        <span class="block text-sm tracking-tighter">{{ $consoleName }}</span>
    </div>
</h1>

@if (!empty($user))
<script>
    function toggleListItem(type)
    {
        $.post('/request/user-game-list/toggle.php', {
            type: type,
            game: {{ $gameID }}
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
