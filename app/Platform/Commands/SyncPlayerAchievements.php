<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Models\Achievement;
use App\Platform\Models\GameHashSet;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\PlayerSession;
use App\Support\Sync\SyncTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class SyncPlayerAchievements extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:player-achievements {username?} {--f|full} {--p|no-post}';
    protected $description = 'Sync player achievements (unlocks)';

    /* Cache some data to avoid repetitive queries */
    private array $achievementGameIds = [];
    private array $playerSessions = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('player_achievements');
    }

    protected function preProcessEntity(object $origin, array $transformed): array
    {
        $userId = $this->getUserId($origin->User);
        if ($userId === null) {
            // unknown user; ignore
            return [];
        }

        $gameId = $this->getGameId($origin->AchievementID);
        if ($gameId === null) {
            // unknown achievement; ignore
            return [];
        }

        // if there's no unlock date, we can't accurately transcribe the data.
        // there were 3 records (of 17 million) like this in the sanitized dump I was using.
        if (!$origin->Date) {
            $this->warn("no date for unlock: {$origin->User}/{$origin->AchievementID}/{$origin->HardcoreMode}");

            return [];
        }
        // convert string to Carbon once since we use the Carbon form several times
        $unlockDate = Carbon::parse($origin->Date);

        // TODO refactor player_sessions to a separate sync loop
        // find the most relevant player_session entry for the achievement
        // $lastPlayerSession = $this->playerSessions[$userId] ?? null;
        // if ($lastPlayerSession == null || $lastPlayerSession->game_id != $gameId) {
        //     $lastPlayerSession = PlayerSession::where('user_id', $userId)
        //         ->where('game_id', $gameId)
        //         ->where('created_at', '<', $unlockDate)
        //         ->orderByDesc('created_at')
        //         ->first();
        //
        //     // if a player_session entry was not found, make sure there's a player_game entry
        //     if (!$lastPlayerSession) {
        /** @var ?PlayerGame $playerGame */
        $playerGame = PlayerGame::where('user_id', $userId)->where('game_id', $gameId)->first();
        if (!$playerGame) {
            $playerGame = new PlayerGame([
                'user_id' => $userId,
                'game_id' => $gameId,
                'created_at' => $unlockDate,
                'updated_at' => null,
            ]);

            // use "timestamps=false" to prevent created_at/updated_at from being set to now()
            $playerGame->save(['timestamps' => false]);
        }
        //     }
        // }

        // TODO refactor player_sessions to a separate sync loop
        // // achievements more than four hours apart are assumed to be in separate sessions
        // $sessionEnd = ($lastPlayerSession) ? $lastPlayerSession->created_at->copy()->addMinutes($lastPlayerSession->duration) : $origin->Date;
        // if ($lastPlayerSession && $unlockDate <= $sessionEnd->addHours(4)) {
        //     // extend the session to contain the achievement
        //     if ($unlockDate > $lastPlayerSession->updated_at) {
        //         $lastPlayerSession->updated_at = $unlockDate;
        //         $lastPlayerSession->duration = $unlockDate->diffInMinutes($lastPlayerSession->created_at);
        //
        //         // use "timestamps=false" to prevent created_at/updated_at from being set to now()
        //         $lastPlayerSession->save(['timestamps' => false]);
        //     }
        //
        //     $this->playerSessions[$userId] = $lastPlayerSession;
        // } else {
        //     // create a new session for the achievement
        //     $gameHashSet = GameHashSet::where('game_id', $gameId)->first();
        //     if ($gameHashSet) {
        //         $lastPlayerSession = new PlayerSession([
        //             'user_id' => $userId,
        //             'game_id' => $gameId,
        //             'game_hash_id' => $gameHashSet->hashes()->first()->id,
        //             'game_hash_set_id' => $gameHashSet->id,
        //             'duration' => 0,
        //         ]);
        //         // have to set these outside of the constructor or it gets overwritten
        //         $lastPlayerSession->setAttribute('created_at', $unlockDate);
        //         $lastPlayerSession->setAttribute('updated_at', $unlockDate);
        //
        //         // use "timestamps=false" to prevent created_at/updated_at from being set to now()
        //         $lastPlayerSession->save(['timestamps' => false]);
        //
        //         // periodically flush the cache as a decent portion of users quit after a few sessions
        //         if (count($this->playerSessions) > 5000) {
        //             $this->playerSessions = [];
        //         }
        //
        //         $this->playerSessions[$userId] = $lastPlayerSession;
        //     }
        // }

        // create a player_achievement record for the unlock
        $transformed = [
            'user_id' => $userId,
            'achievement_id' => $origin->AchievementID,
        ];

        if ($origin->HardcoreMode == 1) {
            $transformed['unlocked_hardcore_at'] = $unlockDate;
        } else {
            $transformed['unlocked_at'] = $unlockDate;
        }

        /** @var ?PlayerAchievement $unlock */
        $unlock = PlayerAchievement::where('achievement_id', $origin->AchievementID)
            ->where('user_id', $userId)->first();
        if ($unlock != null) {
            $transformed['id'] = $unlock->id;

            // if ($unlockDate < $unlock->created_at) {
            //     /* secondary unlock occurred before primary unlock, use the old date as the updated date */
            //     $transformed['updated_at'] = $unlock->created_at;
            // } elseif ($unlockDate > $unlock->created_at) {
            //     /* secondary unlock after the primary unlock, capture the updated date and don't modify the created date */
            //     $transformed['updated_at'] = $origin->Date;
            // } else {
            //     /* secondary unlock at same time as primary unlock. */
            // }
        }

        // TODO: trigger_id

        return $transformed;
    }

    protected function getGameId(int $achievementID): ?int
    {
        $gameId = $this->achievementGameIds[$achievementID] ?? null;
        if ($gameId === null) {
            /** @var ?Achievement $achievement */
            $achievement = Achievement::find($achievementID);
            if (!$achievement) {
                $this->achievementGameIds[$achievementID] = 0;
                $this->warn("unknown achievement: {$achievementID}");

                return null;
            }

            $this->achievementGameIds[$achievementID] = $gameId = $achievement->GameID;
        }

        return ($gameId > 0) ? $gameId : null;
    }
}
