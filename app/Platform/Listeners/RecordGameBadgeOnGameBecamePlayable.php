<?php

declare(strict_types=1);

namespace App\Platform\Listeners;

use App\Platform\Actions\RecordGameBadgeChangeAction;
use App\Platform\Enums\GameBadgeAttribution;
use App\Platform\Events\GameBecamePlayable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecordGameBadgeOnGameBecamePlayable
{
    public function __construct(
        private readonly RecordGameBadgeChangeAction $recordGameBadgeChange,
    ) {
    }

    public function handle(GameBecamePlayable $event): void
    {
        $game = $event->game;
        $iconPath = $game->image_icon_asset_path;

        if (
            $iconPath !== null
            && $game->badges()->whereNull('replaced_at')->where('image_asset_path', $iconPath)->exists()
        ) {
            return;
        }

        try {
            // a badge hiccup must not fail the metrics pipeline
            $this->recordGameBadgeChange->execute($game, $iconPath, GameBadgeAttribution::Live);
        } catch (Throwable $e) {
            Log::warning("Failed to record game badge on publish for game [{$game->id}]: {$e->getMessage()}");
        }
    }
}
