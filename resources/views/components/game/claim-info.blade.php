<?php

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Models\Achievement;
use App\Models\MemoryNote;
use App\Models\PlayerSession;
use App\Enums\Permissions;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

?>

@props([
    'claimData' => [],
    'gameId' => 0,
    'userPermissions' => Permissions::Unregistered,
])

<?php

$userList = '';
$expirationText = '';

$first = true;
foreach ($claimData as $claim) {
    if (!$first) {
        $userList .= ', ';
    }
    $userList .= userAvatar($claim['User'], icon: false);

    if ($first) {
        if ($claim['SetType'] == ClaimSetType::Revision && empty($revisionText)) {
            $userList .= ' (' . ClaimSetType::toString(ClaimSetType::Revision) . ')';
        }

        if ($claim['Status'] == ClaimStatus::InReview) {
            $userList .= ' (' . ClaimStatus::toString(ClaimStatus::InReview) . ')';
        }

        $claimExpiration = Carbon::parse($claim['Expiration']);
        if ($claimExpiration) {
            $claimFormattedDate = $claimExpiration->format('d M Y, g:ia');
            $claimTimeAgoDate = $userPermissions >= Permissions::JuniorDeveloper
                ? ' (' . $claimExpiration->diffForHumans() . ')'
                : '';

            // "Expires on: 12 Jun 2023, 01:28 (1 month from now)"
            $expirationText =
                ($claimExpiration->isPast() ? 'Expired on:' : 'Expires on:') .
                " $claimFormattedDate $claimTimeAgoDate";
        }

        $first = false;
    }
}

$claimantHistory = [];
if ($userPermissions >= Permissions::Moderator) {
    foreach ($claimData as $claim) {
        $playerGame = PlayerSession::where('game_id', $gameId)
            ->join('UserAccounts', 'UserAccounts.ID', '=', 'user_id')
            ->where('UserAccounts.User', '=', $claim['User'])
            ->select(DB::raw('MAX(updated_at) AS last_played'))
            ->first();

        $activity = '';
        $lastPlayed = null;
        if ($playerGame && !empty($playerGame->last_played)) {
            $lastPlayed = Carbon::parse($playerGame->last_played);
            $activity = 'played this game';
        } else {
            // player_sessions only exist after 14 Oct 2023
            $achievement = Achievement::where('GameID', $gameId)
                ->where('Author', $claim['User'])
                ->select(DB::raw('MAX(Updated) AS last_updated'))
                ->first();
            if ($achievement && !empty($achievement->last_updated)) {
                $lastPlayed = Carbon::parse($achievement->last_updated);
                $activity = 'edited an achievement';
            }

            $note = MemoryNote::where('GameID', $gameId)
                ->join('UserAccounts', 'UserAccounts.ID', '=', 'user_id')
                ->where('UserAccounts.User', '=', $claim['User'])
                ->select(DB::raw('MAX(CodeNotes.Updated) AS last_updated'))
                ->first();
            if ($note && !empty($note->last_updated)) {
                $lastUpdated = Carbon::parse($note->last_updated);
                if (!$lastPlayed || $lastUpdated > $lastPlayed) {
                    $lastPlayed = $lastUpdated;
                    $activity = 'edited a note';
                }
            }
        }

        if ($lastPlayed) {
            $formattedDate = $lastPlayed->format('d M Y, g:ia');
            $timeAgo = $lastPlayed->diffForHumans();
            $claimantHistory[] = "{$claim['User']} last $activity on $formattedDate ($timeAgo)";
        }
    }
}
?>

@if (empty($claimData))
    <div>No Active Claims</div>
@else
    <div>
        <p>Claimed by: {!! $userList !!}</p>
        <p>{{ $expirationText }} </p>
        @foreach ($claimantHistory as $history)
            <p>{{ $history }}</p>
        @endforeach
    </div>
@endif
