<?php

use Illuminate\Support\Carbon;

$startedTimeAgo = Carbon::createFromFormat("Y-m-d H:i:s", $claim['Created'])->diffForHumans();
?>

<tr>
    <td class="py-1.5" width="55%">
        <x-game.multiline-avatar
            :gameId="$claim['GameID']"
            :gameTitle="$claim['GameTitle']"
            :gameImageIcon="$claim['GameIcon']"
            :consoleId="$claim['ConsoleID']"
            :consoleName="$claim['ConsoleName']"
        />
    </td>

    <td class="pr-0">{!! userAvatar($claim['User']) !!}</td>
    <td class="smalldate">{{ $startedTimeAgo }}</td>
</tr>