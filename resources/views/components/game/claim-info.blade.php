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
    'achievementSetClaims' => null, // Collection<AchievementSetClaim>
    'userPermissions' => Permissions::Unregistered,
])

<?php

$expirationText = '';

if (!$achievementSetClaims->isEmpty()) {
    // TODO use a policy
    if ($userPermissions >= Permissions::Moderator) {
        $achievementSetClaims->load(['user', 'game', 'game.playerSessions', 'game.achievements', 'game.memoryNotes']);
    } else {
        $achievementSetClaims->load(['user', 'game']);
    }

    $firstClaim = $achievementSetClaims->first();
    $claimFormattedDate = $firstClaim->finished_at->format('d M Y, g:ia');
    $claimTimeAgoDate = $userPermissions >= Permissions::JuniorDeveloper
        ? ' (' . $firstClaim->finished_at->diffForHumans() . ')'
        : '';

    $expirationText = ($firstClaim->finished_at->isPast() ? 'Expired on:' : 'Expires on:')
        . " $claimFormattedDate $claimTimeAgoDate";
}

$userList = collect($achievementSetClaims)->map(function ($achievementSetClaim) use ($achievementSetClaims) {
    $userAvatar = userAvatar($achievementSetClaim->user->display_name ?? 'Deleted User', icon: false);

    if ($achievementSetClaim->getKey() === $achievementSetClaims->first()->getKey()) {
        if ($achievementSetClaim->set_type === ClaimSetType::Revision && empty($revisionText)) {
            $userAvatar .= ' (' . ClaimSetType::toString(ClaimSetType::Revision) . ')';
        }

        if ($achievementSetClaim->status === ClaimStatus::InReview) {
            $userAvatar .= ' (' . ClaimStatus::toString(ClaimStatus::InReview) . ')';
        }
    }

    return $userAvatar;
})->implode(', ');

$claimantHistory = [];
// TODO use a policy
if ($userPermissions >= Permissions::Moderator) {
    foreach ($achievementSetClaims as $achievementSetClaim) {
        if (!$achievementSetClaim->user) {
            continue;
        }

        $playerGame = $achievementSetClaim->game
            ->playerSessions
            ->where('user_id', $achievementSetClaim->user->id)
            ->sortByDesc('updated_at')
            ->first();

        $activity = '';
        $lastPlayed = null;

        if ($playerGame?->updated_at) {
            $lastPlayed = $playerGame->updated_at;
            $activity = 'played this game';
        } else {
            // player_sessions only exist after 14 Oct 2023
            $achievement = $achievementSetClaim->game->achievements
                ->where('user_id', $achievementSetClaim->user->id)
                ->sortByDesc('Updated')
                ->first();

            if ($achievement?->updated_at) {
                $lastPlayed = $achievement->updated_at;
                $activity = 'edited an achievement';
            }

            $note = $achievementSetClaim->game->memoryNotes
                ->where('user_id', $achievementSetClaim->user->id)
                ->sortByDesc('Updated')
                ->first();

            if ($note?->updated_at && (!$lastPlayed || $note->updated_at > $lastPlayed)) {
                $lastPlayed = $note->updated_at;
                $activity = 'edited a note';
            }
        }

        if ($lastPlayed) {
            $formattedDate = $lastPlayed->format('d M Y, g:ia');
            $timeAgo = $lastPlayed->diffForHumans();
            $claimantHistory[] = "{$achievementSetClaim->user->display_name} last {$activity} on {$formattedDate} ({$timeAgo})";
        }
    }
}
?>

@if ($achievementSetClaims->isEmpty())
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
