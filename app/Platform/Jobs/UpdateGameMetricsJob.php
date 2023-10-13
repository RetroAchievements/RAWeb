<?php

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdateGameMetrics;
use App\Platform\Models\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateGameMetricsJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $gameId,
    ) {
    }

    public function handle(): void
    {
        app()->make(UpdateGameMetrics::class)
            ->execute(Game::findOrFail($this->gameId));
    }
}
