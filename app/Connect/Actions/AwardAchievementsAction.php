<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\CanBeDelegated;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\StaticData;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AwardAchievementsAction extends BaseAuthenticatedApiAction
{
    use CanBeDelegated;

    protected array $achievements;
    protected Game $game;
    protected bool $hardcore;
    protected Carbon $when;

    public function execute(
        User $user,
        array $achievements,
        bool $hardcore,
        ?Carbon $when = null,
    ): array {
        $this->user = $user;
        $this->achievements = $achievements;
        if (!empty($achievements)) {
            $achievements[0]->loadMissing('game');
            $this->game = $achievements[0]->game;
        }
        $this->hardcore = $hardcore;
        $this->when = $when ?? Carbon::now();

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['a', 'v', 'k'])) {
            return $this->missingParameters();
        }

        $achievementIds = array_map('intval', explode(',', strval($request->input('a', ''))));

        // ensure achievements exist
        $this->achievements = Achievement::query()
            ->whereIn('id', $achievementIds)
            ->with('game')
            ->get()
            ->all();

        if (empty($this->achievements)) {
            return $this->resourceNotFound('achievement');
        }

        $gameId = null;
        foreach ($this->achievements as $achievement) {
            if (!$gameId) {
                $gameId = $achievement->game_id;
                $this->game = $achievement->game;
            } elseif ($gameId !== $achievement->game_id) {
                return $this->invalidParameter('All provided achievements must be from the same game.');
            }
        }

        // if any requested achievement cannot be delegated, fail.
        $actingUser = $this->user;
        $result = $this->applyDelegationForUnlocks($request, $this->achievements);
        if ($result !== null) {
            return $result;
        }

        $this->hardcore = $request->boolean('h', false);

        // check validation hash (note use of parameters k/u - the parameter may not have the same casing as the model)
        $validationHash = strtolower($request->input('v', ''));

        // delegated unlocks will be rejected if the appropriate validation hash is not provided
        $validationStr = $request->input('a', '') . $request->input('k', '') . ($this->hardcore ? '1' : '0');
        if ($validationHash !== md5($validationStr)) {
            return $this->accessDenied();
        }

        $this->when = Carbon::now();

        return null;
    }

    protected function process(): array
    {
        // Fetch all achievements already awarded to the user.
        $foundPlayerAchievements = PlayerAchievement::where('user_id', $this->user->id)
            ->whereIn('achievement_id', array_column($this->achievements, 'id'))
            ->get();

        // Filter out achievements based on promoted state and whether or not the user has already unlocked them.
        $alreadyAwardedIds = [];
        $awardableAchievements = array_filter($this->achievements, function ($achievement) use (&$alreadyAwardedIds, $foundPlayerAchievements) {
            if (!$achievement->is_promoted) {
                // unpromoted achievements cannot be unlocked.
                return false;
            }

            $foundPlayerAchievement = $foundPlayerAchievements->firstWhere('achievement_id', $achievement->id);
            if (!$foundPlayerAchievement) {
                // Case 1: The achievement hasn't been previously unlocked
                return true;
            } elseif (!$this->hardcore) {
                // Case 2: The achievement was already unlocked in either mode, and a softcore unlock is being requested.
                $alreadyAwardedIds[] = $foundPlayerAchievement->achievement_id;

                return false;
            } elseif ($foundPlayerAchievement->unlocked_hardcore_at !== null) {
                // Case 3: The achievement was already unlocked in hardcore mode, and a hardcore unlock is being requested.
                $alreadyAwardedIds[] = $foundPlayerAchievement->achievement_id;

                return false;
            }

                // Case 4: The achievement was already unlocked in softcore mode, and a hardcore unlock is being requested.
                return true;

        });

        // extend the session if achievements have been or will be awarded.
        if (!empty($awardableAchievements) || !empty($alreadyAwardedIds)) {
            $playerSession = app()->make(ResumePlayerSessionAction::class)->execute(
                $this->user,
                $this->game,
                null,
                timestamp: $this->when,
                userAgent: $this->userAgent,
                ipAddress: $this->ipAddress,
            );
        }

        $newAwardedIds = [];
        if (!empty($awardableAchievements)) {
            $playerGame = PlayerGame::query()
                ->where('user_id', $this->user->id)
                ->where('game_id', $this->game->id)
                ->first();

            $lastAwardedId = null;
            $pointsEarned = 0;
            foreach ($awardableAchievements as $achievement) {
                $newAwardedIds[] = $lastAwardedId = $achievement->id;
                $pointsEarned += $achievement->points;

                if ($this->hardcore) {
                    $this->user->points_hardcore += $achievement->points;

                    if ($playerGame) {
                        $playerGame->achievements_unlocked_hardcore++;
                    }

                    $foundPlayerAchievement = $foundPlayerAchievements->firstWhere('achievement_id', $achievement->id);
                    if ($foundPlayerAchievement) {
                        // if there's a found PlayerAchievement and we're doing a hardcore unlock,
                        // it must be an upgrade from softcore.
                        $this->user->points -= $achievement->points;

                        if ($playerGame) {
                            $playerGame->achievements_unlocked--;
                        }
                    }
                } else {
                    $this->user->points += $achievement->points;

                    if ($playerGame) {
                        $playerGame->achievements_unlocked++;
                    }
                }

                dispatch(new UnlockPlayerAchievementJob($this->user->id, $achievement->id, $this->hardcore))
                    ->onQueue('player-achievements');
            }

            if ($playerGame) {
                $playerGame->save();
            }

            if ($lastAwardedId) {
                StaticData::incrementEach([
                    'NumAwarded' => count($newAwardedIds),
                    'TotalPointsEarned' => $pointsEarned,
                ], [
                    'LastAchievementEarnedID' => $lastAwardedId,
                    'LastAchievementEarnedByUser' => $this->user->display_name,
                    'LastAchievementEarnedAt' => Carbon::now(),
                ]);
            }
        }

        return [
            'Success' => true,
            'Score' => $this->user->points_hardcore,
            'SoftcoreScore' => $this->user->points,
            'ExistingIDs' => $alreadyAwardedIds,
            'SuccessfulIDs' => $newAwardedIds,
        ];
    }
}
