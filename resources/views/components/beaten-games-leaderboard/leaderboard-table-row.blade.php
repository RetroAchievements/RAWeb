<?php

use Illuminate\Support\Carbon;
?>

@props([
    'isHighlighted' => null,
    'myUsername' => '',
    'paginatedRow' => null,
])

<?php
$isHighlighted = $isHighlighted || ($paginatedRow->User === $myUsername);
$lastBeatenDate = Carbon::parse($paginatedRow->last_beaten_date)->format('F j Y');
?>

<tr
    class="hidden sm:table-row {{ $isHighlighted ? 'rounded' : '' }}"
    @if ($isHighlighted) style="outline: thin solid" @endif
>
    <td>
        @if (isset($paginatedRow->rank_number))
            #{{ localized_number($paginatedRow->rank_number) }}
        @endif
    </td>

    <td>{!! userAvatar($paginatedRow->User, iconClass: 'rounded-sm mr-1') !!}</td>

    <td class='py-2.5'>
        <x-game.multiline-avatar
            :gameId="$paginatedRow->last_game_id"
            :gameTitle="$paginatedRow->GameTitle"
            :gameImageIcon="$paginatedRow->GameIcon"
            :consoleName="$paginatedRow->ConsoleName"
        />
    </td>

    <td>
        {{ $lastBeatenDate }}
    </td>

    <td
        class='text-right'
        data-testid="{{ $paginatedRow->User }}-count-{{ $paginatedRow->total_awards }}"
    >
        {{ localized_number($paginatedRow->total_awards) }}
    </td>
</tr>
