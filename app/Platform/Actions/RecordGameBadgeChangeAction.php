<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameBadge;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\GameBadgeAttribution;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RecordGameBadgeChangeAction
{
    public function execute(
        Game $game,
        string $imageAssetPath,
        GameBadgeAttribution $attribution = GameBadgeAttribution::Live,
        ?User $uploadedBy = null,
        ?CarbonInterface $becameCurrentAt = null,
    ): ?GameBadge {
        if (!System::isGameSystem($game->system_id)) {
            return null;
        }

        $transitionAt = $becameCurrentAt ?? now();

        return DB::transaction(function () use ($game, $imageAssetPath, $attribution, $uploadedBy, $transitionAt) {
            if ($this->isPlaceholderPath($imageAssetPath)) {
                $game->badges()
                    ->whereNull('replaced_at')
                    ->update(['replaced_at' => $transitionAt]);

                return null;
            }

            $storagePath = ltrim($imageAssetPath, '/');

            if (!Storage::disk('media')->exists($storagePath)) {
                throw new RuntimeException("Badge file missing at {$imageAssetPath}");
            }

            $sha1 = sha1(Storage::disk('media')->get($storagePath));

            $existingRow = $game->badges()->where('sha1', $sha1)->first();

            if ($existingRow !== null) {
                if ($existingRow->replaced_at !== null) {
                    $game->badges()
                        ->whereNull('replaced_at')
                        ->where('id', '!=', $existingRow->id)
                        ->update(['replaced_at' => $transitionAt]);
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

    private function isPlaceholderPath(string $path): bool
    {
        return in_array($path, [Game::PLACEHOLDER_BADGE_PATH, Game::PLACEHOLDER_IMAGE_PATH], true);
    }
}
