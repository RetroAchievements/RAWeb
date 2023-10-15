<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdatePlayerGameMetrics as UpdatePlayerGameMetricsAction;
use App\Platform\Actions\UpdatePlayerMetrics as UpdatePlayerMetricsAction;
use App\Platform\Models\Game;
use App\Site\Models\User;
use Illuminate\Console\Command;

class UpdatePlayerGameMetrics extends Command
{
    protected $signature = 'ra:platform:player:update-game-metrics
                            {userId : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}
                            {gameIds? : Comma-separated list of game IDs. Leave empty to update all games in player library}
                            {--outdated}';
    protected $description = 'Update player game(s) metrics';

    public function __construct(
        private readonly UpdatePlayerGameMetricsAction $updatePlayerGameMetrics,
        private readonly UpdatePlayerMetricsAction $updatePlayerMetrics
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $userId = $this->argument('userId');
        $outdated = $this->option('outdated');

        $gameIds = collect(explode(',', $this->argument('gameIds') ?? ''))
            ->filter()
            ->map(fn ($id) => (int) $id);

        $user = is_numeric($userId)
            ? User::findOrFail($userId)
            : User::where('User', $userId)->firstOrFail();

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

        $this->info('Updating ' . $playerGames->count() . ' ' . __res('game', $playerGames->count()) . ' for user [' . $user->username . '] [' . $user->id . ']');

        $progressBar = $this->output->createProgressBar($playerGames->count());
        $progressBar->start();

        // if running in sync mode, just call updatePlayerMetrics once manually after updating
        // all of the player_games instead of letting it cascade for every player_game updated.
        $isSync = (config('queue.default') === 'sync');

        foreach ($playerGames as $playerGame) {
            $this->updatePlayerGameMetrics->execute($playerGame, silent: $isSync);
            $progressBar->advance();
        }

        if ($isSync) {
            $this->updatePlayerMetrics->execute($user);
        }

        $progressBar->finish();
        $this->line(PHP_EOL);
    }
}
