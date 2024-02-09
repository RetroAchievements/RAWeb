<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameHashSet;
use App\Platform\Actions\AddImageToGame;
use App\Platform\Actions\UpsertTriggerVersion;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SyncGames extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:games {id?} {--f|full} {--p|no-post} {--m|no-media}';
    protected $description = 'Sync games';

    public function __construct(
        private readonly AddImageToGame $addImageToGameAction,
        private readonly UpsertTriggerVersion $upsertTriggerVersionAction
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        Game::disableSearchSyncing();

        $this->sync('games');
    }

    protected function query(): Builder
    {
        return DB::table('GameData')
            ->select('GameData.*')
            /*
             * by inner joining systems we can make sure only games with a valid system attached are imported
             * only select game data though
             */
            ->join('Console', 'Console.ID', '=', 'GameData.ConsoleID');
    }

    protected function postProcessEntity(object $origin, object $transformed): void
    {
        /** @var ?Game $game */
        $game = Game::find($transformed->id);

        if (!$game) {
            return;
        }

        if (!$this->option('no-media') && config('sync.media_path')) {
            $this->addImageToGameAction->execute($game, config('sync.media_path') . $origin->ImageIcon, 'icon');
        }

        /**
         * Attach the game's rich presence as a versioned trigger onto the compatible game hash set
         */
        /** @var GameHashSet|null $gameHashSet */
        $gameHashSet = $game->gameHashSets()->compatible()->first();
        if ($gameHashSet && $origin->RichPresencePatch) {
            $richPresenceConditions = $this->fixEncoding($origin->RichPresencePatch);
            $this->upsertTriggerVersionAction->execute($gameHashSet, $richPresenceConditions);
        }

        /*
         * TODO remove from tables
         * - game: ImageIcon, ImageTitle, ImageIngame, ImageBoxArt
         * - game: Publisher, Developer, Genre
         */

        /*
         * TODO add to tables
         * - game: forum_enabled
         */

        /*
         * TODO: "tilde tag" in name
         * - remove from name and upsert/assign as tag
         */

        /*
         * TODO: import/move images
         * - copy on s3 to same path as on static media disk - Images folder will be deprecated at some point
         * -
         */

        /*
         * TODO: publisher
         * - create game_publishers
         * - create game_publisher
         */

        /*
         * TODO: developer
         * - create game_developers
         * - create game_developer
         */

        /*
         * TODO: genre
         * - create genres
         * - create game_genre
         */

        /*
         * TODO: forum topic -> forum
         */

        /*
         * TODO: "square bracket" tag in name (hub)
         * - create game_hubs
         * - Theme
         * - Technical
         * - Subseries (Mario Kart)
         * - Subgenre
         * - assign forum_topic_id
         * if the game is an alias for an extra set (bonus, hardcore):
         * - disable forums
         * -
         * - set
         */
    }
}
