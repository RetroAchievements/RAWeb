@props([
    'isHighlighted' => false,
    'paginatedRow' => null,
])

<?php
use Illuminate\Support\Carbon;

$lastBeatenDate = Carbon::parse($paginatedRow->last_beaten_date)->format('F j Y');
?>

<tr
    class="hidden sm:table-row {{ $isHighlighted ? 'rounded' : '' }}"
    @if ($isHighlighted) style="outline: thin solid" @endif
>
    <td>#{{ $paginatedRow->rank_number ? localized_number($paginatedRow->rank_number) : 'na' }}</td>
    <td>{!! userAvatar($paginatedRow->User, iconClass: 'rounded-sm mr-1') !!}</td>
    <td class='py-2.5'>
        <x-game.multiline-avatar
            :gameId="$paginatedRow->most_recent_game_id"
            :gameTitle="$paginatedRow->GameTitle"
            :gameImageIcon="$paginatedRow->GameIcon"
            :consoleName="$paginatedRow->ConsoleName"
        />
    </td>
    <td>
        {{ $lastBeatenDate }}
    </td>
    <td class='text-right'>{{ localized_number($paginatedRow->total_awards) }}</td>
</tr>
