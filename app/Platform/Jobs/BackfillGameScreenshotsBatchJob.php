<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Models\Game;
use App\Platform\Actions\BackfillGameScreenshotsAction;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackfillGameScreenshotsBatchJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int> $gameIds
     */
    public function __construct(
        private readonly array $gameIds,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'backfill-game-screenshots:' . count($this->gameIds),
        ];
    }

    public function handle(): void
    {
        $action = new BackfillGameScreenshotsAction();
        $games = Game::with('system')->whereIn('id', $this->gameIds)->get();

        foreach ($games as $game) {
            try {
                $action->execute($game);
            } catch (Throwable $e) {
                Log::error("BackfillGameScreenshotsBatchJob: failed for game {$game->id}", [
                    'exception' => $e,
                ]);
            }
        }
    }
}
