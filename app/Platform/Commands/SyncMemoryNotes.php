<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameHashSet;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncMemoryNotes extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:memory-notes {gameid?} {--f|full} {--p|no-post}';
    protected $description = 'Sync memory notes';

    /* Cache some data to avoid repetitive queries */
    private array $gameHashSetIds = [];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('memory_notes');
    }

    protected function preProcessEntity(object $origin, array $transformed): array
    {
        /**
         * memory notes are not stored on games anymore but on associated game hash sets instead
         */
        $gameHashSetId = $this->getGameHashSetId($origin->GameID);
        if ($gameHashSetId === null) {
            // unknown game; ignore

            return [];
        }

        /*
         * Address might be 0 even though it's 0 - fix that
         */
        $transformed['address'] = $origin->address ?? 0;

        $transformed['game_hash_set_id'] = $gameHashSetId;

        return $transformed;
    }

    protected function getGameHashSetId(int $gameId): ?int
    {
        $gameHashSetId = $this->gameHashSetIds[$gameId] ?? null;
        if ($gameHashSetId === null) {
            /** @var ?Game $game */
            $game = Game::find($gameId);

            if ($game === null) {
                $this->gameHashSetIds[$gameId] = 0;
                $this->warn("unknown game: {$gameId}");

                return null;
            }

            /** @var ?GameHashSet $gameHashSet */
            $gameHashSet = $game->gameHashSets()->first();

            if ($gameHashSet === null) {
                $this->gameHashSetIds[$gameId] = 0;
                $this->warn("no hash for game: {$gameId}");

                return null;
            }

            $this->gameHashSetIds[$gameId] = $gameHashSetId = $gameHashSet->id;
        }

        return ($gameHashSetId > 0) ? $gameHashSetId : null;
    }
}
