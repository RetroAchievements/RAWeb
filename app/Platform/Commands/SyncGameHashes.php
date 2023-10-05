<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\LinkHashToGame;
use App\Platform\Models\Game;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncGameHashes extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:game-hashes {id?} {--f|full} {--p|no-post}';
    protected $description = 'Sync game hashes';

    public function __construct(
        private readonly LinkHashToGame $linkHashToGameAction
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('game-hashes');
    }

    protected function preProcessEntity(object $origin, array $transformed): array
    {
        /** @var Game $game */
        $game = Game::findOrFail($origin->GameID);

        return [
            'created_at' => $origin->Created,
            'hash' => $origin->MD5, // TODO: Str::lower($origin->MD5),
            'md5' => $origin->MD5, // TODO: Str::lower($origin->MD5),
            'system_id' => $game->getAttribute('system_id'),
        ];
    }

    protected function postProcessEntity(object $origin, object $transformed): void
    {
        /** @var Game $game */
        $game = Game::findOrFail($origin->GameID);

        /*
         * add md5 hash to game's base hash set
         */
        $this->linkHashToGameAction->execute($origin->MD5, $game);
    }
}
