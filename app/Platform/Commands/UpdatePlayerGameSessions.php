<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdatePlayerGameSessions as UpdatePlayerGameSessionsAction;
use App\Platform\Models\Game;
use App\Site\Models\User;
use Illuminate\Console\Command;

class UpdatePlayerGameSessions extends Command
{
    protected $signature = 'ra:platform:player:update-game-sessions
                            {userId : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}
                            {gameIds? : Comma-separated list of game IDs. Leave empty to update all games in player library}';
    protected $description = 'Update player game(s) sessions';

    public function __construct(
        private readonly UpdatePlayerGameSessionsAction $updatePlayerGameSessions
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $userId = $this->argument('userId');

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
        $playerGames = $query->get();

        $this->info('Updating ' . $playerGames->count() . ' ' . __res('game', $playerGames->count()) . ' for user [' . $user->id . ':' . $user->username . ']');

        $progressBar = $this->output->createProgressBar($playerGames->count());
        $progressBar->start();

        foreach ($playerGames as $playerGame) {
            $this->updatePlayerGameSessions->execute($playerGame);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line(PHP_EOL);
    }
}
