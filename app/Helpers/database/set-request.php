<?php

use App\Community\Enums\UserGameListType;
use App\Models\User;
use App\Models\UserGameListEntry;

/**
 * Gets the total and remaining set requests left for the given user.
 */
function getUserRequestsInformation(User $user, int $gameId = -1): array
{
    $requests = UserGameListEntry::getUserSetRequestsInformation($user);

    $requests['used'] = 0;
    $requests['requestedThisGame'] = 0;

    // Determine how many of the users current requests are still valid.
    // Requests made for games that since received achievements do not count towards a used request
    $setRequests = UserGameListEntry::where('user_id', $user->id)
        ->where('type', UserGameListType::AchievementSetRequest)
        ->join('GameData', 'GameData.ID', '=', 'GameId')
        ->select(['GameData.ID', 'GameData.achievements_published']);
    foreach ($setRequests->get() as $request) {
        // If the game does not have achievements then it counts as a legit request
        if ($request['achievements_published'] == 0) {
            $requests['used']++;
        }

        // Determine if we have made a request for the input game
        if ($request['ID'] == $gameId) {
            $requests['requestedThisGame'] = 1;
        }
    }

    $requests['remaining'] = $requests['total'] - $requests['used'];

    return $requests;
}

/**
 * Gets the number of set requests for a given game.
 */
function getSetRequestCount(int $gameId): int
{
    if ($gameId < 1) {
        return 0;
    }

    return UserGameListEntry::where("GameID", $gameId)
        ->where("type", UserGameListType::AchievementSetRequest)
        ->count();
}

/**
 * Gets a list of set requestors for a given game.
 */
function getSetRequestorsList(int $gameId, bool $getEmailInfo = false): array
{
    if ($gameId < 1) {
        return [];
    }

    $query = UserGameListEntry::where('GameID', $gameId)
        ->where('type', UserGameListType::AchievementSetRequest)
        ->with('user');

    $setRequests = $query->get();

    $processedValues = $setRequests->map(function ($setRequest) use ($getEmailInfo) {
        $record = [
            'Requestor' => $setRequest->user->display_name,
        ];

        if ($getEmailInfo) {
            $record['Email'] = $setRequest->user->EmailAddress;
        }

        return $record;
    });

    return $processedValues->toArray();
}

function getUserGameListsContaining(User $user, int $gameId): array
{
    return UserGameListEntry::where("user_id", $user->id)
        ->where("GameID", $gameId)
        ->get(["type"])
        ->pluck("type")
        ->toArray();
}
