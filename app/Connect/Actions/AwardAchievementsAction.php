<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\CanBeDelegated;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
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
    protected ?GameHash $gameHash = null;
    protected bool $hardcore;
    protected Carbon $when;
    public ?PlayerSession $playerSession = null;

    public function execute(
        User $user,
        array $achievements,
        bool $hardcore,
        ?GameHash $gameHash = null,
        ?Carbon $when = null,
        ?string $userAgent = null,
        ?string $ipAddress = null,
    ): array {
        $this->user = $user;
        $this->achievements = $achievements;
        if (!empty($achievements)) {
            $achievements[0]->loadMissing('game');
            $this->game = $achievements[0]->game;
        }
        $this->hardcore = $hardcore;
        $this->gameHash = $gameHash;
        $this->when = $when ?? Carbon::now();
        $this->userAgent = $userAgent;
        $this->ipAddress = $ipAddress;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['a', 'v', 'k'])) {
            return $this->missingParameters();
        }

        $achievementIds = array_map('intval', explode(',', strval($request->input('a', ''))));

        // Ensure achievements exist
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

        // If any requested achievement cannot be delegated, fail.
        $actingUser = $this->user;
        $result = $this->applyDelegationForUnlocks($request, $this->achievements);
        if ($result !== null) {
            return $result;
        }

        $this->hardcore = $request->boolean('h', false);

        // Check validation hash (note use of parameters k/u - the parameter may not have the same casing as the model)
        $validationHash = strtolower($request->input('v', ''));

        // Delegated unlocks will be rejected if the appropriate validation hash is not provided
        $validationStr = $request->input('a', '') . $request->input('k', '') . ($this->hardcore ? '1' : '0');
        if ($validationHash !== md5($validationStr)) {
            return $this->accessDenied();
        }

        $this->when = Carbon::now();

        return null;
    }

    protected function process(): array
    {
        if (!isValidConsoleId($this->game->system_id)) {
            // shouldn't be able to promote achievements for unsupported console, so this is probably unnecessary.
            return $this->unsupportedSystem('Cannot unlock achievements for unsupported console.');
        }

        // Fetch all achievements already awarded to the user.
        $foundPlayerAchievements = PlayerAchievement::where('user_id', $this->user->id)
            ->whereIn('achievement_id', array_column($this->achievements, 'id'))
            ->get();

        $alreadyAwardedIds = [];
        $newAwardedIds = [];
        $eventAchievementIds = [];

        // Filter out achievements based on promoted state and whether or not the user has already unlocked them.
        $numUnpromoted = 0;
        $awardableAchievements = array_filter($this->achievements, function ($achievement) use (&$alreadyAwardedIds, &$eventAchievementIds, &$numUnpromoted, $foundPlayerAchievements) {
            if (!$achievement->is_promoted) {
                // unpromoted achievements cannot be unlocked.
                $numUnpromoted++;

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

                if ($this->user->isRanked()) {
                    // if active event achievements are associated to this achievement,
                    // we still need to dispatch unlock requests for them.
                    foreach ($achievement->eventAchievements()->active($this->when)->get() as $eventAchievement) {
                        $eventAchievementIds[] = $eventAchievement->achievement_id;
                    }
                }

                return false;
            }

            // Case 4: The achievement was already unlocked in softcore mode, and a hardcore unlock is being requested.
            return true;
        });

        // Extend the session if achievements have been or will be awarded.
        if (!empty($awardableAchievements) || !empty($alreadyAwardedIds) || !empty($eventAchievementIds)) {
            $this->playerSession = app()->make(ResumePlayerSessionAction::class)->execute(
                $this->user,
                $this->game,
                $this->gameHash,
                timestamp: $this->when,
                userAgent: $this->userAgent,
                ipAddress: $this->ipAddress,
            );
        }

        // ResumePlayerSessionAction should ensure a playerGame exists
        $playerGame = PlayerGame::query()
            ->where('user_id', $this->user->id)
            ->where('game_id', $this->game->id)
            ->first();

        if (!empty($awardableAchievements)) {
            // Update user's points and playerGame achievements unlocked counts.
            // NOTE: The user's points are not committed, but are returned to client.
            //       The unlock job will trigger a full recalculation of the user's points.
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
            }

            // The client is expecting to receive the number of AchievementsRemaining in the response, and if
            // it's 0, a mastery placard will be shown. Multiple achievements may be unlocked by the client at
            // the same time using separate requests, so we need to update the unlock counts for the
            // player_game (and commit it) as soon as possible so whichever request is processed last _should_
            // return the correct number of remaining achievements. It will be accurately recalculated by the
            // UpdatePlayerGameMetricsAction triggered by an asynchronous UnlockPlayerAchievementJob.
            if ($playerGame) {
                $playerGame->save();
            }

            // Kick off jobs for each unlocked achievement
            foreach ($awardableAchievements as $achievement) {
                dispatch(new UnlockPlayerAchievementJob(
                    $this->user->id,
                    $achievement->id,
                    $this->hardcore,
                    gameHashId: $this->gameHash?->id,
                    timestamp: $this->when,
                    userAgent: $this->userAgent
                ))->onQueue('player-achievements');
            }

            // Update the metrics for the main page
            // NOTE: this double-counts achievements the user is upgrading from softcore to hardcore
            //       and anything the user has previously reset.
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
        } elseif ($numUnpromoted === count($this->achievements)) {
            // If only unpromoted achievements were requested, return an error.
            // Otherwise, just ignore them and return the successful promoted achievements.
            return [
                'Success' => false,
                'Status' => 409,
                'Code' => 'invalid_state',
                'Error' => 'Unpromoted achievements cannot be unlocked.',
            ];
        }

        if (!empty($eventAchievementIds)) {
            // If any achievements were previously earned, but associated to event achievements,
            // we have to kick off unlock requests for the event achievements separately.
            foreach ($eventAchievementIds as $eventAchievementId) {
                dispatch(new UnlockPlayerAchievementJob(
                    $this->user->id,
                    $eventAchievementId,
                    true,
                    gameHashId: $this->gameHash?->id,
                    timestamp: $this->when,
                    userAgent: $this->userAgent
                ))->onQueue('player-achievements');

                $newAwardedIds[] = $eventAchievementId;
            }
        }

        // Calculate the number of achievements remaining to complete the set.
        $achievementsRemaining = $this->game->achievements_published;
        if ($playerGame) {
            if ($this->hardcore) {
                $achievementsRemaining -= $playerGame->achievements_unlocked_hardcore;
            } else {
                $achievementsRemaining -= $playerGame->achievements_unlocked;
            }
        } else {
            $achievementsRemaining -= count($newAwardedIds);
        }

        return [
            'Success' => true,
            'Score' => $this->user->points_hardcore,
            'SoftcoreScore' => $this->user->points,
            'ExistingIDs' => $alreadyAwardedIds,
            'SuccessfulIDs' => $newAwardedIds,
            'AchievementsRemaining' => $achievementsRemaining,
        ];
    }
}
