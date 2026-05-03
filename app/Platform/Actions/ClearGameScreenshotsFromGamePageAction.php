<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Support\Facades\DB;

class ClearGameScreenshotsFromGamePageAction
{
    public function execute(Game $game): int
    {
        return DB::transaction(function () use ($game): int {
            $screenshotIds = $game->gameScreenshots()
                ->pluck('id');

            if ($screenshotIds->isEmpty()) {
                return 0;
            }

            GameScreenshot::whereKey($screenshotIds)
                ->update(['is_primary' => false]);

            $deletedCount = GameScreenshot::whereKey($screenshotIds)
                ->delete();

            $game->syncLegacyScreenshotFields(ScreenshotType::Title);
            $game->syncLegacyScreenshotFields(ScreenshotType::Ingame);

            return $deletedCount;
        });
    }
}
