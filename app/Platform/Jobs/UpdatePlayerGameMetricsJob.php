<?php

namespace App\Platform\Jobs;

use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Models\PlayerGame;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class UpdatePlayerGameMetricsJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly int $gameId,
        private readonly ?bool $hardcore = false,
    ) {
    }

    public function uniqueId(): string
    {
        return $this->userId . '-' . $this->gameId;
    }

    public function handle(): void
    {
        try {
            app()->make(UpdatePlayerGameMetricsAction::class)
                ->execute(
                    PlayerGame::where('user_id', '=', $this->userId)
                        ->where('game_id', '=', $this->gameId)
                        ->firstOrFail(),
                    $this->hardcore,
                );
        } catch (Exception $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            $this->fail($exception);
        }
    }
}
