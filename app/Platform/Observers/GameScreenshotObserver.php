<?php

declare(strict_types=1);

namespace App\Platform\Observers;

use App\Models\GameScreenshot;
use App\Platform\Enums\GameScreenshotStatus;
use App\Support\Media\CreateLegacyScreenshotPngAction;
use Illuminate\Support\Facades\Storage;

class GameScreenshotObserver
{
    public function saved(GameScreenshot $screenshot): void
    {
        if (!$screenshot->wasRecentlyCreated && $screenshot->wasChanged('type')) {
            $this->handleTypeChange($screenshot);

            return;
        }

        if (!$screenshot->is_primary) {
            return;
        }

        // For updates, only sync when is_primary actually changed (not on description edits etc).
        // For creates, wasChanged() is unreliable (syncChanges() only runs in performUpdate),
        // so we check wasRecentlyCreated instead.
        if (!$screenshot->wasRecentlyCreated && !$screenshot->wasChanged('is_primary')) {
            return;
        }

        $this->ensureLegacyPng($screenshot);

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

    /**
     * Handle primary reassignment when a screenshot's type changes.
     * All updates use updateQuietly() to avoid re-triggering saved()
     * while getOriginal() still reflects the pre-save state. If we
     * don't do this, we end up with an infinite loop.
     */
    private function handleTypeChange(GameScreenshot $screenshot): void
    {
        $oldType = $screenshot->getOriginal('type');
        $wasOldPrimary = $screenshot->getOriginal('is_primary');

        // If this was the primary of the old type, promote the next approved
        // screenshot of that type so the old type isn't left without a primary.
        if ($wasOldPrimary) {
            $next = GameScreenshot::where('game_id', $screenshot->game_id)
                ->where('type', $oldType)
                ->where('id', '!=', $screenshot->id)
                ->approved()
                ->orderBy('order_column')
                ->first();

            if ($next) {
                $next->updateQuietly(['is_primary' => true]);
                $this->ensureLegacyPng($next);
            }
        }

        // Auto-promote to primary if no primary exists for the new type yet.
        $newTypeHasPrimary = GameScreenshot::where('game_id', $screenshot->game_id)
            ->where('type', $screenshot->type)
            ->where('is_primary', true)
            ->where('id', '!=', $screenshot->id)
            ->exists();

        if (!$newTypeHasPrimary) {
            $screenshot->updateQuietly([
                'is_primary' => true,
                'status' => GameScreenshotStatus::Approved,
            ]);

            $this->ensureLegacyPng($screenshot);
        } elseif ($wasOldPrimary) {
            // The new type already has a primary. Demote this screenshot
            // so we don't end up with two primaries of the same type.
            $screenshot->updateQuietly(['is_primary' => false]);
        }

        $screenshot->game->syncLegacyScreenshotFields();
    }

    /**
     * When a non-primary screenshot is promoted to primary, its media record
     * may not have a legacy_path value in custom_properties (only auto-primary
     * screenshots get it at upload time). Create the legacy PNG now by
     * downloading the original from S3.
     */
    private function ensureLegacyPng(GameScreenshot $screenshot): void
    {
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
    }
}
