<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;

class WriteGameSortTitleFromGameTitleAction
{
    public function execute(Game $game, string $originalTitle, bool $shouldRespectCustomSortTitle = true): void
    {
        // Compute the original sort title based on the original game title.
        // We do this to determine if a developer has given the game a custom sort title, because
        // if the game indeed has a custom sort title, we may not want to override it.
        if ($game->sort_title && $shouldRespectCustomSortTitle) {
            $originalSortTitle = $this->computeSortTitle($originalTitle);

            if ($game->sort_title !== $originalSortTitle) {
                return;
            }
        }

        // Otherwise, compute and save the new sort title.
        $game->sort_title = $this->computeSortTitle($game->title);
        $game->save();
    }

    /**
     * Sort titles ensure games in lists are sorted properly.
     * For titles starting with "~", the sort order is determined by the content
     * within the "~" markers followed by the content after the "~". This ensures
     * that titles with "~" are grouped together and sorted alphabetically based
     * on their designated categories and then by their actual game title.
     *
     * The "~" prefix is retained in the SortTitle of games with "~" to ensure these
     * games are sorted at the end of the list, maintaining a clear separation from
     * non-prefixed titles. This approach allows game titles to be grouped and sorted
     * in a specific order:
     *
     * 1. Non-prefixed titles are sorted alphabetically at the beginning of the list.
     * 2. Titles prefixed with "~" are grouped at the end, sorted first by the category
     *    specified within the "~" markers, and then alphabetically by the title following
     *    the "~".
     */
    private function computeSortTitle(string $title): string
    {
        $sortTitle = mb_strtolower($title);

        if ($sortTitle[0] === '~') {
            $endOfFirstTilde = strpos($sortTitle, '~', 1);
            if ($endOfFirstTilde !== false) {
                $withinTildes = substr($sortTitle, 1, $endOfFirstTilde - 1);
                $afterTildes = trim(substr($sortTitle, $endOfFirstTilde + 1));

                $sortTitle = '~' . $withinTildes . ' ' . $afterTildes;
            }
        }

        return $sortTitle;
    }
}
