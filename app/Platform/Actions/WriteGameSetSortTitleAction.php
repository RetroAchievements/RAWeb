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
            $originalSortTitle = $computeAction->execute($this->prepareHubTitle($originalTitle));

            if ($gameSet->sort_title !== $originalSortTitle) {
                return null;
            }
        }

        // Otherwise, compute and save the new sort title.
        $gameSet->sort_title = $computeAction->execute($this->prepareHubTitle($gameSet->title));
        if ($shouldSave) {
            $gameSet->save();
        }

        return $gameSet->sort_title;
    }

    /**
     * Strip brackets and remove leading articles from the inner content.
     * eg: "[Series - The Legend of Zelda]" -> "Series - Legend of Zelda"
     *
     * This ultimately allows us to have sort titles that are a bit better,
     * eg: "[Series - The Legend of Zelda]" becomes "series - legend of zelda".
     */
    private function prepareHubTitle(string $title): string
    {
        $title = trim($title, '[]');

        if (str_contains($title, ' - ')) {
            [$category, $innerTitle] = explode(' - ', $title, 2);
            $innerTitle = preg_replace('/^(the|a|an)\s+/i', '', $innerTitle);
            $title = $category . ' - ' . $innerTitle;
        }

        return $title;
    }
}
