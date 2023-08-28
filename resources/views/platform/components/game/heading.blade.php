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

$addVisibility = '';
$removeVisibility = '';
$onLists = !empty($user) ? getUserGameListsContaining($user, $gameID) : [];

if (in_array(UserGameListType::Play, $onLists)) {
    $addVisibility = 'hidden';
} else {
    $removeVisibility = 'hidden';
}
?>

<h1 class="text-h3">
    <span class="block mb-1">{!! $renderedTitle !!}</span>

    @if (!empty($user))
    <div class="md:float-right">
        <div id='add-to-play-list' class="w-[18px] h-[18px] {{ $addVisibility }}" title="Add to Want to Play list" cursor="pointer"
            onClick="toggleListItem('{{ UserGameListType::Play}}')">
            <x-icon.add-to-list />
        </div>

        <div id='remove-from-play-list' class="w-[18px] h-[18px] {{ $removeVisibility }}" title="Remove from Want to Play list" cursor="pointer"
            onClick="toggleListItem('{{ UserGameListType::Play}}')">
            <x-icon.remove-from-list />
        </div>
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
        });
    }
</script>
@endif
