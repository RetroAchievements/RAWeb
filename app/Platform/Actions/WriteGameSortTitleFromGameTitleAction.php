<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;

class WriteGameSortTitleFromGameTitleAction
{
    public function execute(
        Game $game,
        string $originalTitle,
        bool $shouldRespectCustomSortTitle = true,
        bool $shouldSaveGame = true,
    ): ?string {
        $computeAction = new ComputeGameSortTitleAction();

        // Compute the original sort title based on the original game title.
        // We do this to determine if a developer has given the game a custom sort title, because
        // if the game indeed has a custom sort title, we may not want to override it.
        if ($game->sort_title && $shouldRespectCustomSortTitle) {
            $originalSortTitle = $computeAction->execute($originalTitle);

            if ($game->sort_title !== $originalSortTitle) {
                return null;
            }
        }

        // Otherwise, compute and save the new sort title.
        $game->sort_title = $computeAction->execute($game->title);
        if ($shouldSaveGame) {
            $game->save();
        }

        return $game->sort_title;
    }
}
