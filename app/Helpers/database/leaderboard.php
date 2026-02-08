<?php

use App\Enums\ClientSupportLevel;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Enums\ValueFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

// TODO migrate to action
function SubmitLeaderboardEntry(
    User $user,
    Leaderboard $leaderboard,
    int $newEntry,
    ?string $validation,
    ?GameHash $gameHash = null,
    ?Carbon $timestamp = null,
    ClientSupportLevel $clientSupportLevel = ClientSupportLevel::Full,
): array {
    $retVal = ['Success' => true];

    $leaderboard->loadMissing('game');
    if ($leaderboard->game->system_id && !isValidConsoleId($leaderboard->game->system_id)) {
        $retVal['Success'] = false;
        $retVal['Error'] = "Cannot submit entry for unsupported console";

        return $retVal;
    }

    $retVal['LBData'] = [
        'Format' => $leaderboard->format,
        'LeaderboardID' => $leaderboard->id,
        'GameID' => $leaderboard->game_id,
        'Title' => $leaderboard->title,
        'LowerIsBetter' => $leaderboard->rank_asc,
    ];
    $retVal['Score'] = $newEntry;
    $retVal['ScoreFormatted'] = ValueFormat::format($newEntry, $leaderboard->format);

    $timestamp ??= Carbon::now();
    $playerSession = app()->make(ResumePlayerSessionAction::class)->execute(
        $user,
        $leaderboard->game,
        ($gameHash && !$gameHash->isMultiDiscGameHash()) ? $gameHash : null,
        timestamp: $timestamp,
    );

    // First check if there's an existing entry (including soft-deleted)
    $existingLeaderboardEntry = LeaderboardEntry::withTrashed()
        ->where('leaderboard_id', $leaderboard->id)
        ->where('user_id', $user->id)
        ->first();

    if ($existingLeaderboardEntry) {
        if (!$clientSupportLevel->allowsHardcoreUnlocks()) {
            $retVal['BestScore'] = $existingLeaderboardEntry->score;
        } elseif ($existingLeaderboardEntry->trashed()
            || $leaderboard->isBetterScore($newEntry, $existingLeaderboardEntry->score)) {

            // Update the score first before saving/restoring to avoid race conditions with observers.
            $existingLeaderboardEntry->score = $newEntry;
            $existingLeaderboardEntry->player_session_id = $playerSession->id;
            $existingLeaderboardEntry->updated_at = $timestamp;

            if ($existingLeaderboardEntry->trashed()) {
                $existingLeaderboardEntry->restore();
            } else {
                $existingLeaderboardEntry->save();
            }

            $retVal['BestScore'] = $newEntry;
        } else {
            // No change made.
            $retVal['BestScore'] = $existingLeaderboardEntry->score;
        }
    } elseif (!$clientSupportLevel->allowsHardcoreUnlocks()) {
        $retVal['BestScore'] = 0;
    } else {
        // No existing leaderboard entry. Let's insert a new one, using
        // updateOrCreate to handle potential race conditions if the client
        // is rapid-firing off submissions to the server.
        $entry = LeaderboardEntry::updateOrCreate(
            [
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $user->id,
            ],
            [
                'score' => $newEntry,
                'player_session_id' => $playerSession->id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'deleted_at' => null, // Ensure the entry is not soft-deleted.
            ]
        );

        $retVal['BestScore'] = $newEntry;
    }

    $retVal['RankInfo'] = [
        'NumEntries' => $leaderboard->entries()->count(),
    ];

    $retVal['RankInfo']['Rank'] = $leaderboard->getRank($retVal['BestScore']);
    $entries = $leaderboard->sortedEntries();

    $getEntries = function (Builder $query) {
        $entries = $query
            ->with('user')
            ->limit(10)
            ->get()
            ->map(fn ($entry) => [
                'User' => $entry->user->display_name,
                'Score' => $entry->score,
                'DateSubmitted' => $entry->updated_at->unix(),
            ])
            ->toArray();

        $index = 1;
        $rank = 0;
        $score = null;
        foreach ($entries as &$entry) {
            if ($entry['Score'] !== $score) {
                $score = $entry['Score'];
                $rank = $index;
            }

            $entry['Rank'] = $rank;
            $index++;
        }

        return $entries;
    };

    $retVal['TopEntries'] = $getEntries($entries->getQuery());

    $retVal['TopEntriesFriends'] = $getEntries($entries->whereHas('user', function ($query) use ($user) {
        $friends = $user->followedUsers()->pluck('related_user_id');
        $friends[] = $user->id;
        $query->whereIn('id', $friends);
    })->getQuery());

    return $retVal;
}
