<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdatePlayerGameMetrics as UpdatePlayerGameMetricsAction;
use App\Platform\Models\Game;
use App\Site\Models\User;
use Illuminate\Console\Command;

class UpdatePlayerGameMetrics extends Command
{
    protected $signature = 'ra:platform:player:update-game-metrics
                            {username}
                            {gameIds? : Comma-separated list of game IDs. Leave empty to update all games in player library}
                            {--outdated}';
    protected $description = 'Update player game(s) metrics';

    public function __construct(
        private readonly UpdatePlayerGameMetricsAction $updatePlayerGameMetrics
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $outdated = $this->option('outdated');

        $gameIds = collect(explode(',', $this->argument('gameIds') ?? ''))
            ->filter()
            ->map(fn ($id) => (int) $id);

        $user = User::where('User', $this->argument('username'))->firstOrFail();

        $query = $user->playerGames()
            ->with(['user', 'game']);
        if ($gameIds->isNotEmpty()) {
            $query->whereIn(
                'game_id',
                Game::whereIn('id', $gameIds)->get()->pluck('id')
            );
        }
        if ($outdated) {
            $query->whereNotNull('update_status');
        }
        $playerGames = $query->get();

        $progressBar = $this->output->createProgressBar($playerGames->count());
        $progressBar->start();

        foreach ($playerGames as $playerGame) {
            $this->updatePlayerGameMetrics->execute($playerGame);
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
