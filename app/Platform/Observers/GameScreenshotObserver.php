<?php

declare(strict_types=1);

namespace App\Platform\Observers;

use App\Models\GameScreenshot;
use App\Support\Media\CreateLegacyScreenshotPngAction;
use Illuminate\Support\Facades\Storage;

class GameScreenshotObserver
{
    public function saved(GameScreenshot $screenshot): void
    {
        if (!$screenshot->is_primary) {
            return;
        }

        // For updates, only sync when is_primary actually changed (not on description edits etc).
        // For creates, wasChanged() is unreliable (syncChanges() only runs in performUpdate),
        // so we check wasRecentlyCreated instead.
        if (!$screenshot->wasRecentlyCreated && !$screenshot->wasChanged('is_primary')) {
            return;
        }

        // When a non-primary screenshot is promoted to primary, its media record
        // may not have a legacy_path value in custom_properties (only auto-primary
        // screenshots get it at upload time). Create the legacy PNG now by downloading
        // the original from S3.
        $media = $screenshot->media;
        if ($media && !$media->getCustomProperty('legacy_path')) {
            $fileContents = Storage::disk('s3')->get($media->getPath());
            if ($fileContents) {
                $legacyPath = (new CreateLegacyScreenshotPngAction())->execute($fileContents);
                if ($legacyPath) {
                    $media->setCustomProperty('legacy_path', $legacyPath);
                    $media->save();
                }
            }
        }

        $screenshot->game->syncLegacyScreenshotFields();
    }

    public function deleted(GameScreenshot $screenshot): void
    {
        if (!$screenshot->is_primary) {
            return;
        }

        $game = $screenshot->game;

        // Promote the next approved screenshot of the same type.
        $next = $game->gameScreenshots()
            ->ofType($screenshot->type)
            ->approved()
            ->orderBy('order_column')
            ->first();

        if ($next) {
            // Promoting the next screenshot triggers saved(), which handles the sync.
            $next->update(['is_primary' => true]);

            return;
        }

        // No remaining screenshots of this type. Reset to placeholder.
        $game->syncLegacyScreenshotFields($screenshot->type);
    }
}
