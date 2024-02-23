<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Models\Game;
use App\Platform\Actions\PopulateCoreGameAchievementSet;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PopulateCoreGameAchievementSetJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $gameId,
    ) {
    }

    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return config('queue.default') === 'sync' ? '' : $this->gameId;
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            Game::class . ':' . $this->gameId,
        ];
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $game = Game::find($this->gameId);

        if (!$game) {
            return;
        }

        app()->make(PopulateCoreGameAchievementSet::class)
            ->execute(Game::findOrFail($this->gameId));
    }
}
