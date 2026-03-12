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
        $this->backfillType($game, $game->image_ingame_asset_path, ScreenshotType::Ingame);
        $this->backfillType($game, $game->image_title_asset_path, ScreenshotType::Title);
    }

    private function backfillType(Game $game, ?string $assetPath, ScreenshotType $type): void
    {
        if (empty($assetPath) || $assetPath === Game::PLACEHOLDER_IMAGE_PATH) {
            return;
        }

        $fileContents = Storage::disk('media')->get($assetPath);
        if ($fileContents === null) {
            Log::warning("BackfillGameScreenshots: file not found on media disk for game {$game->id}: {$assetPath}");

            return;
        }

        $hash = sha1($fileContents);

        // Check idempotency, and bail if this type already has this exact image.
        // Scoped by type so identical content used for both title and ingame still creates both records.
        $alreadyExists = $game->gameScreenshots()
            ->ofType($type)
            ->whereHas('media', fn ($q) => $q->where('custom_properties->sha1', $hash))
            ->exists();

        if ($alreadyExists) {
            return;
        }

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
