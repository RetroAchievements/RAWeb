<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Models\PlayerGame;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SyncPlayerGames extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:player-games {username?} {--f|full} {--p|no-post}';
    protected $description = 'Sync player games';

    public function __construct(private UpdatePlayerGameMetricsAction $updatePlayerGameMetricsAction)
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('player_games');
    }

    protected function query(): Builder
    {
        return DB::table($this->referenceTable)
            ->select(['player_achievements.*', 'Achievements.GameID'])
            ->leftJoin('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id');
    }

    protected function preProcessEntity(object $origin, array $transformed): array
    {
        $playerGame = PlayerGame::where('user_id', '=', $origin->user_id)
            ->where('game_id', '=', $origin->GameID)
            ->first();

        if ($playerGame) {
            // return empty to skip
            return [];
        }

        return [
            'user_id' => $origin->user_id,
            'game_id' => $origin->GameID,
        ];
    }

    protected function postProcessEntity(object $origin, object $transformed): void
    {
        $playerGame = PlayerGame::where('user_id', '=', $transformed->user_id)
            ->where('game_id', '=', $transformed->game_id)
            ->first();

        $this->updatePlayerGameMetricsAction->execute($playerGame);

        // update only the minimum of fields
        // detailed

        // $playerGame->first_unlock_at =
        // $playerGame->created_at =
        //
        // dump($playerGame);
        // dd($origin);
    }
}
