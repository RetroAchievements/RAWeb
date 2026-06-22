<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Support\Facades\DB;

class ClearGameScreenshotsFromGamePageAction
{
    public function execute(Game $game, ?User $causer = null): int
    {
        return DB::transaction(function () use ($game, $causer): int {
            $affectedIds = $game->gameScreenshots()
                ->whereIn('status', [
                    GameScreenshotStatus::Approved->value,
                    GameScreenshotStatus::Pending->value,
                ])
                ->pluck('id');

            if ($affectedIds->isEmpty()) {
                return 0;
            }

            $previousPrimaries = $game->gameScreenshots()
                ->approved()
                ->primary()
                ->with('media')
                ->get()
                ->keyBy(fn (GameScreenshot $screenshot) => $screenshot->type->value);

            $count = GameScreenshot::whereKey($affectedIds)
                ->update([
                    'is_primary' => false,
                    'status' => GameScreenshotStatus::Rejected->value,
                ]);

            $game->syncLegacyScreenshotFields(ScreenshotType::Title);
            $game->syncLegacyScreenshotFields(ScreenshotType::Ingame);

            foreach (ScreenshotType::cases() as $type) {
                $previous = $previousPrimaries->get($type->value);
                if (!$previous) {
                    continue;
                }

                (new LogPrimaryScreenshotChangeAction())->execute(
                    $game,
                    $type,
                    $previous,
                    null,
                    $causer,
                );
            }

            return $count;
        });
    }
}
