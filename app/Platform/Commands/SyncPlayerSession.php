<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Models\GameHashSet;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\PlayerSession;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncPlayerSession extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:player-sessions {username?} {--f|full} {--p|no-post}';
    protected $description = 'Sync player sessions (unlocks)';

    /* Cache some data to avoid repetitive queries */
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
        $this->sync('player_sessions');
    }

    protected function preProcessEntity(object $origin, array $transformed): array
    {
        // TODO
        $userId = $origin->user_id;
        $achievementId = $origin->achievement_id;
        $unlockDate = $origin->unlocked_at;
        $gameId = null;

        // find the most relevant player_session entry for the achievement
        $lastPlayerSession = $this->playerSessions[$userId] ?? null;
        if ($lastPlayerSession == null || $lastPlayerSession->game_id != $gameId) {
            $lastPlayerSession = PlayerSession::where('user_id', $userId)
                ->where('game_id', $gameId)
                ->where('created_at', '<', $unlockDate)
                ->orderByDesc('created_at')
                ->first();

            // if a player_session entry was not found, make sure there's a player_game entry
            if (!$lastPlayerSession) {
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
            }
        }

        // achievements more than four hours apart are assumed to be in separate sessions
        $sessionEnd = ($lastPlayerSession) ? $lastPlayerSession->created_at->copy()->addMinutes($lastPlayerSession->duration) : $origin->Date;
        if ($lastPlayerSession && $unlockDate <= $sessionEnd->addHours(4)) {
            // extend the session to contain the achievement
            if ($unlockDate > $lastPlayerSession->updated_at) {
                $lastPlayerSession->updated_at = $unlockDate;
                $lastPlayerSession->duration = $unlockDate->diffInMinutes($lastPlayerSession->created_at);

                // use "timestamps=false" to prevent created_at/updated_at from being set to now()
                $lastPlayerSession->save(['timestamps' => false]);
            }

            $this->playerSessions[$userId] = $lastPlayerSession;
        } else {
            // create a new session for the achievement
            $gameHashSet = GameHashSet::where('game_id', $gameId)->first();
            if ($gameHashSet) {
                $lastPlayerSession = new PlayerSession([
                    'user_id' => $userId,
                    'game_id' => $gameId,
                    'game_hash_id' => $gameHashSet->hashes()->first()->id,
                    'game_hash_set_id' => $gameHashSet->id,
                    'duration' => 0,
                ]);
                // have to set these outside of the constructor or it gets overwritten
                $lastPlayerSession->created_at = $unlockDate;
                $lastPlayerSession->updated_at = $unlockDate;

                // use "timestamps=false" to prevent created_at/updated_at from being set to now()
                $lastPlayerSession->save(['timestamps' => false]);

                // periodically flush the cache as a decent portion of users quit after a few sessions
                if (count($this->playerSessions) > 5000) {
                    $this->playerSessions = [];
                }

                $this->playerSessions[$userId] = $lastPlayerSession;
            }
        }

        return $transformed;
    }
}
