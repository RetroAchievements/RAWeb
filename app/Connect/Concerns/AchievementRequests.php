<?php

declare(strict_types=1);

namespace App\Connect\Concerns;

use App\Platform\Jobs\UnlockPlayerAchievementJob;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerGame;
use App\Site\Models\User;
use Illuminate\Http\Request;

trait AchievementRequests
{
    /**
     * Used by RAIntegration
     *
     * @since 1.0
     */
    protected function unlocksMethod(Request $request): array
    {
        $this->authorize('viewAny', PlayerAchievement::class);

        $request->validate(
            [
                'g' => 'required|integer',
                'h' => 'required|boolean',
            ],
            $messages = [],
            $attributes = [
                'g' => 'Game ID',
                'h' => 'Hardcore Flag',
            ]
        );

        $gameId = $request->input('g');
        $hardcore = (bool) $request->input('h');

        // TODO: should be $request->user('connect-token')->games()->find($gameId)
        /** @var ?Game $game */
        $game = Game::find($gameId);
        abort_if($game === null, 404, 'Game with ID "' . $gameId . '" not found');

        /**
         * TODO: abort if game has not yet been assigned to user
         */
        // abort_unless($game !== null, 404, 'Game with ID "' . $gameId . '" is not attached to this user');

        /**
         * TODO: get unlocks
         */
        // $unlocks = $playerGame->achievements()->get(['id']);
        $unlocks = [];

        return [
            'userUnlocks' => $unlocks,
            'gameId' => $gameId,
            'hardcoreMode' => $hardcore,
        ];
    }

    /**
     * TODO
     *
     * @since 1.0
     */
    protected function achievementwondataMethod(Request $request): array
    {
        $this->authorize('viewAny', PlayerAchievement::class);

        // $friendsOnly = seekPOSTorGET('f', 0, 'integer');
        // $response['Offset'] = $offset;
        // $response['Count'] = $count;
        // $response['FriendsOnly'] = $friendsOnly;
        // $response['AchievementID'] = $achievementID;
        // $response['Response'] = getAchievementRecentWinnersData($achievementID, $offset, $count, $user, $friendsOnly);

        return [
        ];
    }

    /**
     * TODO
     *
     * @since 1.0
     */
    protected function awardachievementMethod(Request $request): array
    {
        $this->authorize('create', PlayerAchievement::class);

        // TODO: validate game session/that user is actually playing this game

        $request->validate(
            [
                'a' => 'required|integer',
                'v' => 'nullable|string',
                'h' => 'required|integer',
            ],
            $messages = [],
            $attributes = [
                'a' => 'Achievement ID',
                'v' => 'Validation Hash',
                'h' => 'Hardcore Flag',
            ]
        );

        $achievementId = $request->input('a');
        $hardcore = $request->input('h');

        // TODO: should be $request->user('connect-token')->games()->find($gameId)
        /** @var ?Achievement $achievement */
        $achievement = Achievement::find($achievementId);
        abort_if($achievement === null, 404, 'Achievement with ID "' . $achievementId . '" not found');

        // TODO "Unofficial achievements cannot be unlocked"

        /** @var User $user */
        $user = $request->user('connect-token');

        // TODO: validate sent hash
        // $request->input('v') === $achievement->unlockValidationHash($user, (int) $hardcore);

        // fail silently - might be an unauthenticated request (RetroArch)
        dispatch(new UnlockPlayerAchievementJob($user->id, $achievement->id, (bool) $hardcore))
            ->onQueue('player-achievements');

        $playerGame = PlayerGame::where('user_id', $user->id)
            ->where('game_id', $achievement->game_id)
            ->first();
        $remaining = 0;
        if ($playerGame) {
            $remaining = $playerGame->achievements_total - $playerGame->achievements_unlocked;
        }

        // TODO respond with optimistically updated score values
        return [
            'Score' => $user->points,
            'SoftcoreScore' => (int) $user->points_softcore,
            'AchievementID' => (int) $achievementId,
            'AchievementsRemaining' => $remaining,
        ];
    }
}
