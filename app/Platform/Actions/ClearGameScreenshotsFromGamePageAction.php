<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Support\Facades\DB;

class ClearGameScreenshotsFromGamePageAction
{
    public function execute(Game $game): int
    {
        return DB::transaction(function () use ($game): int {
            $affectedIds = $game->gameScreenshots()
                ->whereIn('status', [
                    GameScreenshotStatus::Approved->value,
                    GameScreenshotStatus::Pending->value,
                ])
                ->pluck('id');

            if ($affectedIds->isEmpty()) {
                return 0;
            }

            $count = GameScreenshot::whereKey($affectedIds)
                ->update([
                    'is_primary' => false,
                    'status' => GameScreenshotStatus::Rejected->value,
                ]);

            $game->syncLegacyScreenshotFields(ScreenshotType::Title);
            $game->syncLegacyScreenshotFields(ScreenshotType::Ingame);

            return $count;
        });
    }
}
