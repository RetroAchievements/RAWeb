<?php

declare(strict_types=1);

namespace App\Site\Commands;

use App\Site\Actions\DeleteAvatarAction;
use App\Site\Actions\UpdateAvatarAction;
use App\Site\Models\User;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SyncUsers extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:users {id?} {--f|full} {--p|no-post} {--m|no-media}';
    protected $description = 'Sync users';

    public function __construct(
        private UpdateAvatarAction $updateAvatarAction,
        private DeleteAvatarAction $deleteAvatarAction,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        User::disableSearchSyncing();

        $this->sync('users');
    }

    protected function query(): Builder
    {
        return DB::table('UserAccounts');
    }

    protected function preProcessEntity(object $origin, array $transformed): array
    {
        if (in_array($transformed['motto'], ['Unknown'])) {
            $transformed['motto'] = null;
        }

        $transformed['display_name'] = $transformed['User'];

        return $transformed;
    }

    protected function postProcessEntity(object $origin, object $transformed): void
    {
        // dump($transformed);
        // dump($origin);

        /** @var ?User $user */
        $user = User::find($transformed->id);

        if (!$user) {
            return;
        }

        /*
         * update avatar
         */
        if (!$this->option('no-media') && config('sync.media_path')) {
            $file = config('sync.media_path') . '/UserPic/' . $origin->User . '.png';
            if (file_exists($file)) {
                $this->updateAvatarAction->execute($user, $file);
            } else {
                $this->deleteAvatarAction->execute($user);
            }
        }

        /*
         * touch user to make sure it's added to the scout search index
         * do not do this. run the scout import after the sync instead
         */
        // $user->touch();

        // TODO move preferences
        // $table->unsignedSmallInteger('preferences_legacy')->nullable();

        // /**
        //  * add initial set of user-games entries by "started game" in activity feed
        //  */
        // DB::connection('mysql')->getPdo()
        //     ->exec("INSERT IGNORE INTO user_games (user_id, game_id, created_at)
        //         SELECT ua.user_id, ua.game_id, ua.created_at
        //         FROM (
        //             SELECT user_id, data AS game_id, created_at
        //             FROM user_activity_log
        //             WHERE activity_type_id = " . \App\Domain\Accounts\Models\UserActivity::StartedPlaying . "
        //             AND data > 0
        //             GROUP BY user_id, game_id, created_at
        //             ORDER BY created_at
        //         ) AS ua;");
        //
        // /**
        //  * TODO: set manual unlock at for achievements where no user_game entry exists by activity
        //  */
        //
        // /**
        //  * add additional user-games entries by user_achievement unlocks (might not have appeared in feed because of manual unlocks)
        //  */
        // DB::connection('mysql')->getPdo()
        //     ->exec("INSERT IGNORE INTO user_games (user_id, game_id, created_at)
        //         SELECT ua.user_id, ua.game_id, ua.created_at
        //         FROM (
        //             SELECT user_id, data AS game_id, created_at
        //             FROM user_activity_log
        //             WHERE activity_type_id = " . \App\Domain\Accounts\Models\UserActivity::StartedPlaying . "
        //             AND data > 0
        //             GROUP BY user_id, game_id, created_at
        //             ORDER BY created_at
        //         ) AS ua;");
    }
}
