<?php

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdateGameAchievementsMetrics;
use App\Platform\Models\Game;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;
use RectorPrefix202308\Illuminate\Contracts\Broadcasting\ShouldBeUnique;

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

    public function uniqueId(): string
    {
        return (string) $this->gameId;
    }

    public function handle(): void
    {
        try {
            app()->make(UpdateGameAchievementsMetrics::class)
                ->execute(Game::findOrFail($this->gameId));
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            $this->fail($exception);
        }
    }
}
