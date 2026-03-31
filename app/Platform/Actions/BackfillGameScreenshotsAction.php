<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameScreenshot;
use App\Platform\Enums\GameScreenshotStatus;
use App\Platform\Enums\ScreenshotType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackfillGameScreenshotsAction
{
    public function execute(Game $game): void
    {
        // Use getRawOriginal() to bypass the media restriction accessor.
        // The backfill needs the real stored path even for restricted games.
        $this->backfillType($game, $game->getRawOriginal('image_ingame_asset_path'), ScreenshotType::Ingame);
        $this->backfillType($game, $game->getRawOriginal('image_title_asset_path'), ScreenshotType::Title);
    }

    private function backfillType(Game $game, ?string $assetPath, ScreenshotType $type): void
    {
        if (empty($assetPath) || $assetPath === Game::PLACEHOLDER_IMAGE_PATH) {
            return;
        }

        // If this type already has any screenshot, ensure one is primary and move on.
        // This handles both re-runs of the backfill and games that already received
        // uploads via Filament before the backfill ran.
        $latestOfType = $game->gameScreenshots()->ofType($type)->latest()->first();
        if ($latestOfType) {
            if (!$latestOfType->is_primary && !$game->gameScreenshots()->ofType($type)->primary()->exists()) {
                $latestOfType->update(['is_primary' => true]);
            }

            return;
        }

        $fileContents = Storage::disk('media')->get($assetPath);
        if ($fileContents === null) {
            Log::warning("BackfillGameScreenshots: file not found on media disk for game {$game->id}: {$assetPath}");

            return;
        }

        $hash = sha1($fileContents);

        $tempPath = tempnam(sys_get_temp_dir(), 'backfill-') . '.png';

        try {
            file_put_contents($tempPath, $fileContents);

            $dimensions = getimagesize($tempPath);
            $width = $dimensions ? $dimensions[0] : null;
            $height = $dimensions ? $dimensions[1] : null;

            $media = $game
                ->addMedia($tempPath)
                ->withCustomProperties([
                    'sha1' => $hash,
                    'legacy_path' => $assetPath,
                ])
                ->toMediaCollection('screenshots');

            GameScreenshot::create([
                'game_id' => $game->id,
                'media_id' => $media->id,
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'is_primary' => true,
                'status' => GameScreenshotStatus::Approved,
            ]);
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}
