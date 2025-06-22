<?php

namespace App\Platform\Jobs;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Services\PlayerGameActivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePlayerGamePlaytimeJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly int $gameId,
    ) {
    }

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return config('queue.default') === 'sync' ? '' : $this->userId . '-' . $this->gameId . '-playtime';
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            User::class . ':' . $this->userId,
            Game::class . ':' . $this->gameId,
        ];
    }

    public function handle(): void
    {
        $playerGame = PlayerGame::where('user_id', '=', $this->userId)
            ->where('game_id', '=', $this->gameId)
            ->with(['user', 'game'])
            ->first();

        if (!$playerGame) {
            // might've been deleted
            return;
        }

        $activityService = new PlayerGameActivityService();
        $activityService->initialize($playerGame->user, $playerGame->game);
        $summary = $activityService->summarize();

        // Only save if playtime actually changed.
        if ($playerGame->playtime_total !== $summary['totalPlaytime']) {
            $playerGame->playtime_total = $summary['totalPlaytime'];
            $playerGame->save();
        }
    }
}
