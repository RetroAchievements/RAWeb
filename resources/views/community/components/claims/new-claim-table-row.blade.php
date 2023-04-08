<?php

use Illuminate\Support\Carbon;

$startedTimeAgo = Carbon::createFromFormat("Y-m-d H:i:s", $claim['Created'])->diffForHumans();
?>

<tr>
    <td class="pr-0">{!! gameAvatar($claim, label: false, tooltip: false) !!}</td>
    <td class="w-full">{!! gameAvatar($claim, icon: false, tooltip: false) !!}</td>
    <td class="pr-0">{!! userAvatar($claim['User'], label: false) !!}</td>
    <td>{!! userAvatar($claim['User'], icon: false) !!}</td>
    <td class="smalldate">{{ $startedTimeAgo }}</td>
</tr>