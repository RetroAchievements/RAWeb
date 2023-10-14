<?php

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdateGameAchievementsMetrics;
use App\Platform\Models\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateGameAchievementsMetricsJob implements ShouldQueue, ShouldBeUnique
{
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

    public function handle(): void
    {
        app()->make(UpdateGameAchievementsMetrics::class)
            ->execute(Game::findOrFail($this->gameId));
    }
}
