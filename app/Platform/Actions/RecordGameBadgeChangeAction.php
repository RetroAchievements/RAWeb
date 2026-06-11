<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameBadge;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\GameBadgeAttribution;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RecordGameBadgeChangeAction
{
    public function execute(
        Game $game,
        ?string $imageAssetPath,
        GameBadgeAttribution $attribution = GameBadgeAttribution::Live,
        ?User $uploadedBy = null,
        ?CarbonInterface $becameCurrentAt = null,
    ): ?GameBadge {
        if (!System::isGameSystem($game->system_id)) {
            return null;
        }

        // don't track icons while the set is WIP. game_badges only holds badges that were live
        // while the game was playable. the activitylog still records all underlying icon changes.
        if (!($game->achievements_published > 0)) {
            return null;
        }

        $transitionAt = $becameCurrentAt ?? now();

        return DB::transaction(function () use ($game, $imageAssetPath, $attribution, $uploadedBy, $transitionAt) {
            if (GameBadge::isPlaceholderPath($imageAssetPath)) {
                $game->badges()
                    ->whereNull('replaced_at')
                    ->update(['replaced_at' => $transitionAt]);

                return null;
            }

            $storagePath = ltrim($imageAssetPath, '/');
            $fileContents = $this->getBadgeFileContents($storagePath);

            if ($fileContents === null) {
                throw new RuntimeException("Badge file missing at {$imageAssetPath}");
            }

            $sha1 = sha1($fileContents);

            // a placeholder re-uploaded under a unique filename means the badge was removed,
            // so retire the current row without recording the placeholder as a real badge.
            if (GameBadge::isPlaceholderSha1($sha1)) {
                $game->badges()
                    ->whereNull('replaced_at')
                    ->update(['replaced_at' => $transitionAt]);

                return null;
            }

            $existingRow = $game->badges()->withTrashed()->where('sha1', $sha1)->first();

            if ($existingRow !== null) {
                if ($existingRow->replaced_at !== null) {
                    $game->badges()
                        ->whereNull('replaced_at')
                        ->where('id', '!=', $existingRow->id)
                        ->update(['replaced_at' => $transitionAt]);
                }

                if ($existingRow->trashed()) {
                    $existingRow->restore();
                }

                $existingRow->update([
                    'image_asset_path' => $imageAssetPath,
                    'became_current_at' => $transitionAt,
                    'replaced_at' => null,
                    'attribution_source' => $attribution,
                    'uploaded_by_user_id' => $uploadedBy?->id,
                ]);

                return $existingRow;
            }

            $game->badges()
                ->whereNull('replaced_at')
                ->update(['replaced_at' => $transitionAt]);

            return $game->badges()->create([
                'image_asset_path' => $imageAssetPath,
                'sha1' => $sha1,
                'attribution_source' => $attribution,
                'uploaded_by_user_id' => $uploadedBy?->id,
                'became_current_at' => $transitionAt,
                'replaced_at' => null,
            ]);
        });
    }

    private function getBadgeFileContents(string $storagePath): ?string
    {
        foreach (['media', 's3'] as $diskName) {
            /** @var Filesystem $disk */
            $disk = Storage::disk($diskName);

            if ($disk->exists($storagePath)) {
                return $disk->get($storagePath);
            }
        }

        return null;
    }
}
