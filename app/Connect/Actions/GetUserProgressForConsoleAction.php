<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class GetUserProgressForConsoleAction extends BaseAuthenticatedApiAction
{
    protected int $consoleId;

    public function execute(User $user, int $consoleId): array
    {
        $this->user = $user;
        $this->consoleId = $consoleId;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['c'])) {
            return $this->missingParameters();
        }

        $this->consoleId = $request->integer('c', 0);

        return null;
    }

    protected function process(): array
    {
        $games = Game::where('ConsoleID', $this->consoleId)
            ->where('achievements_published', '>', 0)
            ->get();

        /** @var Collection<int, PlayerGame> $playerGames */
        $playerGames = $this->user->playerGames()
            ->whereIn('game_id', $games->pluck('id'))
            ->get()
            ->keyBy('game_id');

        $result = [];
        foreach ($games as $game) {
            /** @var ?PlayerGame $playerGame */
            $playerGame = $playerGames->get($game->id);

            $gameDetails = ['Achievements' => $game->achievements_published];

            if ($unlocked = $playerGame?->achievements_unlocked) {
                $gameDetails['Unlocked'] = $unlocked;

                if ($hardcore = $playerGame->achievements_unlocked_hardcore) {
                    $gameDetails['UnlockedHardcore'] = $hardcore;
                }
            }

            $result[$game->id] = $gameDetails;
        }

        return [
            'Success' => true,
            'Response' => $result,
        ];
    }
}
