<?php

use Illuminate\Support\Carbon;

$claimSetType = $claim['SetType'];
$finishedTimeAgo = Carbon::createFromFormat("Y-m-d H:i:s", $claim['DoneTime'])->diffForHumans();
?>

<tr>
    <td class="pr-0">{!! gameAvatar($claim, label: false, tooltip: false) !!}</td>
    <td class="w-full">{!! gameAvatar($claim, icon: false, tooltip: false) !!}</td>
    <td class="pr-0">{!! userAvatar($claim['User'], label: false) !!}</td>
    <td>{!! userAvatar($claim['User'], icon: false) !!}</td>
    <td><x-claim-set-type :claimSetType=$claimSetType /></td>
    <td class="smalldate">{{ $finishedTimeAgo }}</td>
</tr>
