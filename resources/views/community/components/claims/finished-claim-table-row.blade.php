<?php

use App\Community\Enums\ClaimSetType;
use Illuminate\Support\Carbon;

$claimSetTypeCopy = ClaimSetType::toString(ClaimSetType::NewSet);
if ($claim['SetType'] !== ClaimSetType::NewSet) {
    $claimSetTypeCopy = ClaimSetType::toString(ClaimSetType::Revision);
}

$finishedTimeAgo = Carbon::createFromFormat("Y-m-d H:i:s", $claim['DoneTime'])->diffForHumans();
?>

<tr>
    <td class="pr-0">{!! gameAvatar($claim, label: false, tooltip: false) !!}</td>
    <td class="w-full">{!! gameAvatar($claim, icon: false, tooltip: false) !!}</td>
    <td class="pr-0">{!! userAvatar($claim['User'], label: false) !!}</td>
    <td>{!! userAvatar($claim['User'], icon: false) !!}</td>
    <td>{{ $claimSetTypeCopy }}</td>
    <td class="smalldate">{{ $finishedTimeAgo }}</td>
</tr>
