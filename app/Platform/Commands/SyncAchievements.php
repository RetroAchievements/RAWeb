<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SyncAchievements extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:achievements {id?} {--f|full} {--p|no-post}';
    protected $description = 'Sync achievements';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        Achievement::disableSearchSyncing();

        $this->sync('achievements');
    }

    protected function query(): Builder
    {
        return DB::table('Achievements')
            ->select('Achievements.*', 'UserAccounts.ID as AuthorID')
            /*
             * by inner joining games we can make sure only achievements with a valid game attached are imported
             * only select achievement data though
             */
            ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
            ->leftJoin('UserAccounts', 'UserAccounts.ID', '=', 'Achievements.user_id');
    }

    protected function postProcessEntity(object $origin, object $transformed): void
    {
        /* @var Achievement $achievement */
        // $achievement = Achievement::withTrashed()->find($transformed->id);

        // dump($transformed);
        // dd($origin);

        /*
         * TODO: achievements
         * act as if the achievement was uploaded
         *
         * achievement is in core:
         * - add achievement, make it public
         * - add a new AchievementSet with type 'base' if it doesn't exist yet and make it public (default for 'base')
         * - assign the set to this game via achievement_set_game
         * - attach achievement to set
         * - assign set to all players via achievement_set_user
         * achievement is unofficial:
         * - add achievement, make it public
         * - for each author add a new AchievementSet with type 'community' and make it public
         * - assign the set to this game via achievement_set_game
         * - assign set to the respective author via achievement_set_user
         * - discard the game_id; achievements are assigned to games through AchievementSets
         */
    }
}
