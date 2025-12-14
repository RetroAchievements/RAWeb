<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameSet;

class WriteGameSetSortTitleAction
{
    public function execute(
        GameSet $gameSet,
        string $originalTitle,
        bool $shouldRespectCustomSortTitle = true,
        bool $shouldSave = true,
    ): ?string {
        $computeAction = new ComputeSortTitleAction();

        // Compute the original sort title based on the original game set title.
        // We do this to determine if the sort title has been manually customized, because
        // if the game set indeed has a custom sort title, we may not want to override it.
        if ($gameSet->sort_title && $shouldRespectCustomSortTitle) {
            $originalSortTitle = $computeAction->execute($originalTitle);

            if ($gameSet->sort_title !== $originalSortTitle) {
                return null;
            }
        }

        // Otherwise, compute and save the new sort title.
        $gameSet->sort_title = $computeAction->execute($gameSet->title);
        if ($shouldSave) {
            $gameSet->save();
        }

        return $gameSet->sort_title;
    }
}
