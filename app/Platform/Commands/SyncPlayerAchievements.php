<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Models\Achievement;
use App\Platform\Models\PlayerAchievement;
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
            return [];
        }
        // convert string to Carbon once since we use the Carbon form several times
        $unlockDate = Carbon::parse($origin->Date);

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
        }

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

                return null;
            }

            $this->achievementGameIds[$achievementID] = $gameId = $achievement->GameID;
        }

        return ($gameId > 0) ? $gameId : null;
    }
}
