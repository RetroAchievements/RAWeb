<?php

/*
 *  API_GetUserWantToPlayList - returns a list of GameIDs that a user has saved on their WantToPlayList
 *    u : username
 *
 *  array
 *   int     GameID                id of the game 
 */

use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\User;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
    'c' => 'nullable|integer|min:0',
    'o' => 'nullable|integer|min:0',
]);

$user = User::firstWhere('User', request()->query('u'));
if (!$user) {
    return response()->json([]);
}

$count = min((int) request()->query('c', '10'), 50);
$offset = (int) request()->query('o');

$wantToPlayData = getWantToPlayList($user, $offset, $count)
$gameArray = []

if (!empty($wantToPlayData)) {
    $gameIDs = [];
    foreach ($wantToPlayData as $wantToPlay) {
        $gameIDs[] = $wantToPlay['GameID'];
    }

    foreach ($gameIDs['GameID'] as $nextGameID) {
        $game = Game::with('system')->find($gameId);
        if ($game) {
            $gameAchievementSetClaims = AchievementSetClaim::with('user')->where('game_id', $gameId)->get();
            $gameAchievements = Achievement::where('GameID', $gameId)->where('Flags', $flag)->findMany($game->achievements);

            $gameData = [
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ConsoleID' => $game->ConsoleID,
                'ForumTopicID' => $game->ForumTopicID,
                'Flags' => null, // Always '0', this is different in the extended endpoint test for some reason
                'ImageIcon' => $game->ImageIcon,
                'ImageTitle' => $game->ImageTitle,
                'ImageIngame' => $game->ImageIngame,
                'ImageBoxArt' => $game->ImageBoxArt,
                'Publisher' => $game->Publisher,
                'Developer' => $game->Developer,
                'Genre' => $game->Genre,
                'Released' => $game->Released,
                'IsFinal' => $game->IsFinal,
                'RichPresencePatch' => md5($game->RichPresencePatch),
                'GuideURL' => $game->GuideURL,
                'Updated' => $game->Updated->format('Y-m-d\TH:i:s.u\Z'),
            ];

            // Use maps to structure the data with how legacy API consumers might expect it to be returned.
            if (!$gameAchievements->isEmpty()) {
                $gameAchievements->loadMissing('developer');

                $gameListAchievements = $gameAchievements->keyBy('ID')->map(function ($am) {
                    return [
                        'ID' => $am->ID,
                        'NumAwarded' => $am->unlocks_total,
                        'NumAwardedHardcore' => $am->unlocks_hardcore_total,
                        'Title' => $am->Title,
                        'Description' => $am->Description,
                        'Points' => $am->Points,
                        'TrueRatio' => $am->TrueRatio,
                        'Author' => $am->developer?->display_name,
                        'DateModified' => Carbon::parse($am->DateModified)->format('Y-m-d H:i:s'),
                        'DateCreated' => Carbon::parse($am->DateCreated)->format('Y-m-d H:i:s'),
                        'BadgeName' => $am->BadgeName,
                        'DisplayOrder' => $am->DisplayOrder,
                        'MemAddr' => md5($am->MemAddr),
                        'type' => $am->type,
                    ];
                });
            } else {
                $gameListAchievements = new ArrayObject();
            }

            if (!$gameAchievementSetClaims) {
                $gameClaims = [];
            } else {
                $gameClaims = $gameAchievementSetClaims->map(function ($gc) {
                    return [
                        'User' => $gc->user->display_name,
                        'SetType' => $gc->SetType,
                        'GameID' => $gc->game_id,
                        'ClaimType' => $gc->ClaimType,
                        'Created' => Carbon::parse($gc->Created)->format('Y-m-d H:i:s'),
                        'Expiration' => Carbon::parse($gc->Finished)->format('Y-m-d H:i:s'),
                    ];
                });
            }

            array_push($gameArray, array_merge(
                $gameData,
                [
                    'ConsoleName' => $game->system->Name,
                    'ParentGameID' => $game->getParentGame()?->id,
                    'NumDistinctPlayers' => $game->players_total,
                    'NumAchievements' => count($gameAchievements),
                    'Achievements' => $gameListAchievements,
                    'Claims' => $gameClaims
                ]
            ));
        }
    }
}

return response()->json($gameArray);
