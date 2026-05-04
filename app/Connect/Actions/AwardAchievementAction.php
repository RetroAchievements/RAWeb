<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Connect\Support\CanBeDelegated;
use App\Connect\Support\GeneratesConnectWarnings;
use App\Enums\ClientSupportLevel;
use App\Models\Achievement;
use App\Models\GameHash;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\StaticData;
use App\Models\User;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AwardAchievementAction extends BaseAuthenticatedApiAction
{
    use CanBeDelegated;
    use GeneratesConnectWarnings;

    protected Achievement $achievement;
    protected bool $hardcore;
    protected ?GameHash $gameHash = null;
    protected Carbon $when;

    public function execute(User $user, Achievement $achievement, bool $hardcore, ?GameHash $gameHash = null, ?Carbon $when = null): array
    {
        $this->user = $user;
        $this->achievement = $achievement;
        $this->hardcore = $hardcore;
        $this->gameHash = $gameHash;
        $this->when = $when ?? Carbon::now();
        $this->clientSupportLevel = ClientSupportLevel::Full;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['a'])) {
            return $this->missingParameters();
        }

        // ignore warning achievement
        $achievementId = $request->integer('a', 0);
        if ($achievementId === Achievement::CLIENT_WARNING_ID) {
            return [
                'Success' => true,
                'AchievementID' => $achievementId,
                'AchievementsRemaining' => 9999,
                'Score' => $this->user->points_hardcore,
                'SoftcoreScore' => $this->user->points,
            ];
        }

        // ensure achievement exists
        $achievement = Achievement::query()
            ->where('id', $achievementId)
            ->with('game')
            ->first();
        if (!$achievement) {
            return $this->resourceNotFound('achievement');
        }
        $this->achievement = $achievement;

        $this->hardcore = $request->boolean('h', false);

        // determine if request is being delegated
        $actingUser = $this->user;
        $result = $this->applyDelegationForUnlock($request, $this->achievement);
        if ($result !== null) {
            return $result;
        }

        // check validation hash

        // ignore negative values and offsets greater than max.
        // clamping offset will invalidate validationHash.
        $maxOffset = 14 * 24 * 60 * 60; // 14 days
        $offset = min(max((int) $request->input('o', 0), 0), $maxOffset);

        $validationHash = strtolower($request->input('v', ''));

        if ($this->user != $actingUser) {
            // delegated unlocks will be rejected if the appropriate validation hash is not provided
            if ($validationHash !== $this->achievement->unlockValidationHash($this->user, (int) $this->hardcore, $offset)) {
                return $this->accessDenied();
            }
        } elseif (empty($validationHash)) {
            $this->addSmell($request, 'no_validation');
        } elseif ($validationHash !== $this->achievement->unlockValidationHash($this->user, (int) $this->hardcore, $offset)) {
            if ($offset !== 0 || $validationHash !== $this->achievement->unlockValidationHash($this->user, (int) $this->hardcore, $offset, true)) {
                $this->addSmell($request, 'bad_validation');

                // hash failed - ignore offset
                $offset = 0;
            }
        }

        // check client support level
        $this->validateClient($request, $this->achievement->game);

        if (!$this->clientSupportLevel->allowsHardcoreUnlocks() && $this->hardcore) {
            $this->hardcore = false;
        }

        // capture game hash (if provided)
        $gameHashMD5 = $request->input('m', '');
        if ($gameHashMD5) {
            $this->gameHash = GameHash::whereMd5($gameHashMD5)->first();
        }

        // if a smell was detected, flesh out the warning
        if ($this->connectWarning) {
            $this->connectWarning->related_type = 'achievement';
            $this->connectWarning->related_id = $this->achievement->id;
            $this->connectWarning->hardcore = (int) $this->hardcore;
            $this->connectWarning->offset = $request->has('o') ? (int) $request->input('o') : null;
            $this->connectWarning->validation_hash = mb_strimwidth($request->input('v', ''), 0, 40, "..."); // capture unnormalized parameter
        }

        $this->when = Carbon::now()->subSeconds($offset);

        return null;
    }

    protected function process(): array
    {
        if ($this->clientSupportLevel === ClientSupportLevel::Blocked) {
            return $this->unsupportedClient();
        }

        if (!$this->achievement->is_promoted) {
            return [
                'Success' => false,
                'Status' => 409,
                'Code' => 'invalid_state',
                'Error' => 'Unpromoted achievements cannot be unlocked.',
            ];
        }

        $this->achievement->loadMissing('game');
        if (!isValidConsoleId($this->achievement->game->system_id)) {
            // shouldn't be able to promote achievements for unsupported console, so this is probably unnecessary.
            return $this->unsupportedSystem('Cannot unlock achievements for unsupported console.');
        }

        $playerGame = PlayerGame::query()
            ->where('user_id', $this->user->id)
            ->where('game_id', $this->achievement->game_id)
            ->first();

        $hasRegular = false;
        $hasHardcore = false;
        $playerAchievement = PlayerAchievement::query()
            ->where('user_id', $this->user->id)
            ->where('achievement_id', $this->achievement->id)
            ->first();
        if ($playerAchievement) {
            $hasRegular = ($playerAchievement->unlocked_at != null);
            $hasHardcore = ($playerAchievement->unlocked_hardcore_at != null);
        }
        $alreadyAwarded = $this->hardcore ? $hasHardcore : $hasRegular;

        if (!$alreadyAwarded) {
            // The client is expecting to receive the number of AchievementsRemaining in the response, and if
            // it's 0, a mastery placard will be shown. Multiple achievements may be unlocked by the client at
            // the same time using separate requests, so we need to update the unlock counts for the
            // player_game (and commit it) as soon as possible so whichever request is processed last _should_
            // return the correct number of remaining achievements. It will be accurately recalculated by the
            // UpdatePlayerGameMetricsAction triggered by an asynchronous UnlockPlayerAchievementJob.
            // Also update user points for the response, but don't immediately commit them to avoid unnecessary
            // DB writes.
            if ($this->hardcore && !$hasHardcore) {
                $this->user->points_hardcore += $this->achievement->points;
                if ($hasRegular) {
                    $this->user->points -= $this->achievement->points;
                }

                if ($playerGame) {
                    $playerGame->achievements_unlocked_hardcore++;

                    if ($hasRegular) {
                        $playerGame->achievements_unlocked--;
                    }

                    $playerGame->save();
                }
            } elseif (!$this->hardcore && !$hasRegular) {
                $this->user->points += $this->achievement->points;

                if ($playerGame) {
                    $playerGame->achievements_unlocked++;
                    $playerGame->save();
                }
            }
        }

        $achievementsRemaining = $this->achievement->game->achievements_published;
        if ($playerGame) {
            if ($this->hardcore) {
                $achievementsRemaining -= $playerGame->achievements_unlocked_hardcore;
            } else {
                $achievementsRemaining -= $playerGame->achievements_unlocked;
            }
        } else {
            $achievementsRemaining--;
        }

        $retVal = [
            'Success' => true,
            'AchievementID' => $this->achievement->id,
            'AchievementsRemaining' => $achievementsRemaining,
            'Score' => $this->user->points_hardcore,
            'SoftcoreScore' => $this->user->points,
        ];

        if ($alreadyAwarded) {
            if ($this->hardcore && $this->user->isRanked() && $this->achievement->eventAchievements()->active()->exists()) {
                // if event achievements are active, assume they still need to be unlocked and indicate success.
            } else {
                $retVal['Success'] = false;
            }

            // =============================================================================
            // ===== DO NOT CHANGE THESE MESSAGES ==========================================
            // The client detects the "User already has" and does not report them as errors.
            if ($this->hardcore) {
                $retVal['Error'] = "User already has this achievement unlocked in hardcore mode.";
            } else {
                $retVal['Error'] = "User already has this achievement unlocked.";
            }
            // =============================================================================
        } else {
            StaticData::incrementEach([
                'NumAwarded' => 1,
                'TotalPointsEarned' => $this->achievement->points,
            ], [
                'LastAchievementEarnedID' => $this->achievement->id,
                'LastAchievementEarnedByUser' => $this->user->display_name,
                'LastAchievementEarnedAt' => Carbon::now(),
            ]);

            // this job actually unlocks the achievement and updates all the associated metrics
            // asynchronously in the background so we can respond to the client as quickly as possible.
            dispatch(new UnlockPlayerAchievementJob($this->user->id, $this->achievement->id, $this->hardcore,
                                                    gameHashId: $this->gameHash?->id,
                                                    timestamp: $this->when))
                    ->onQueue('player-achievements');
        }

        return $retVal;
    }
}
