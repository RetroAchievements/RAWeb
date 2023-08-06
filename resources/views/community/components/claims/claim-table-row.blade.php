<?php
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use Illuminate\Support\Carbon;

$claimSetTypeCopy = ClaimSetType::toString(ClaimSetType::NewSet);
if ($claim['SetType'] !== ClaimSetType::NewSet) {
    $claimSetTypeCopy = ClaimSetType::toString(ClaimSetType::Revision);
}

$targetTimestamp = $claim['Status'] === ClaimStatus::Active ? $claim['Created'] : $claim['DoneTime'];
$timeAgo = Carbon::createFromFormat("Y-m-d H:i:s", $targetTimestamp)->diffForHumans();
?>

<tr>
    <td class="py-1.5">
        <x-game.multiline-avatar
            :gameId="$claim['GameID']"
            :gameTitle="$claim['GameTitle']"
            :gameImageIcon="$claim['GameIcon']"
            :consoleId="$claim['ConsoleID']"
            :consoleName="$claim['ConsoleName']"
        />
    </td>

    <td class="pr-0">{!! userAvatar($claim['User']) !!}</td>
    <td>{{ $claimSetTypeCopy }}</td>
    <td 
        class="smalldate" 
        title="{{ getNiceDate(strtotime($targetTimestamp)) }}"
    >
        {{ $timeAgo }}
    </td>
</tr>
