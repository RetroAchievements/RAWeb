<?php

use Illuminate\Support\Carbon;
use LegacyApp\Community\Enums\ClaimSetType;

[$claimSetTypeStr, $claimSetTypeIcon] = ['', ''];
if ($claim['SetType'] === ClaimSetType::NewSet) {
    $claimSetTypeStr = ClaimSetType::toString(ClaimSetType::NewSet);
} else {
    $claimSetTypeStr = ClaimSetType::toString(ClaimSetType::Revision);
}

$finishedTimeAgo = Carbon::createFromFormat("Y-m-d H:i:s", $claim['DoneTime'])->diffForHumans();
?>

<tr>
    <td class="pr-0">{!! gameAvatar($claim, label: false, tooltip: false) !!}</td>
    <td class="w-full">{!! gameAvatar($claim, icon: false, tooltip: false) !!}</td>
    <td class="pr-0">{!! userAvatar($claim['User'], label: false) !!}</td>
    <td>{!! userAvatar($claim['User'], icon: false) !!}</td>
    <td>
        <div class="flex items-center gap-1 text-xs">
            <x-claim-set-type-icon />
            <span>{{ $claimSetTypeStr }}</span>
        </div>
    </td>
    <td class="smalldate">{{ $finishedTimeAgo }}</td>
</tr>
