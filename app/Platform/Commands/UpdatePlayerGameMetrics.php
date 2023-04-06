<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Models\PlayerGame;
use App\Site\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class UpdatePlayerGameMetrics extends Command
{
    protected $signature = 'ra:platform:player:update-game-metrics {username} {gameId?}';
    protected $description = 'Update player games and achievement-set metrics';

    public function __construct(private UpdatePlayerGameMetricsAction $updatePlayerGameMetricsAction)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $username = $this->argument('username');
        $gameId = $this->argument('gameId');

        /** ?User $user */
        $user = User::firstWhere('User', $username);
        if (!$user) {
            $this->error('User not found');

            return;
        }

        $playerGames = new Collection();
        if ($gameId) {
            /** @var ?PlayerGame $playerGame */
            $playerGame = $user->playerGames()->with(['user', 'game'])->firstWhere('game_id', $gameId);
            if (!$playerGame) {
                $this->error('Player game not found');

                return;
            }
            $playerGames->add($playerGame);
        } else {
            $playerGames = $user->playerGames()->with(['user', 'game'])->get();
        }

        foreach ($playerGames as $playerGame) {
            $this->info('Updating player [' . $playerGame->user_id . '] game [' . $playerGame->game_id . ']');
            $this->updatePlayerGameMetricsAction->execute($playerGame);
        }
    }
}
