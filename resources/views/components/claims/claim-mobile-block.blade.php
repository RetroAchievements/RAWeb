<?php
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use Illuminate\Support\Carbon;

$claimSetTypeCopy = ClaimSetType::toString(ClaimSetType::NewSet);
if ($claim['SetType'] !== ClaimSetType::NewSet) {
    $claimSetTypeCopy = ClaimSetType::toString(ClaimSetType::Revision);
}

$targetTimestamp = ClaimStatus::isActive($claim['Status']) ? $claim['Created'] : $claim['DoneTime'];
$timeAgo = Carbon::createFromFormat("Y-m-d H:i:s", $targetTimestamp)->diffForHumans();

$gameSystemIconSrc = getSystemIconUrl($claim['ConsoleID']);
?>

<div class="rounded p-2 bg-embed w-full">
    <div class="flex items-center gap-x-2.5">
        {!! gameAvatar($claim, label: false, iconSize: 48, iconClass: 'border-0 rounded-sm badgeimg') !!}

        <div class="flex flex-col gap-y-0.5 w-full">
            <a href="{{ route('game.show', $claim['GameID']) }}" class="leading-4 cursor-pointer">
                <x-game-title :rawTitle="$claim['GameTitle']" />
            </a>

            <div class="flex justify-between w-full text-xs tracking-tighter">
                <div>
                    <span>{{ $claim['ConsoleName'] }}</span>
                </div>
            </div>

            <div class="text-xs flex justify-between">
                {!! userAvatar($claim['User'], iconSize: 14) !!}
                <span class="text-xs tracking-tighter">{{ $claimSetTypeCopy }}, {{ $timeAgo }}</span>
            </div>
        </div>
    </div>
</div>