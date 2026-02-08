<?php

declare(strict_types=1);

namespace App\Support\Search\Actions;

class CalculateTitleRelevanceAction
{
    /**
     * Returns a float from 0.0 (not relevant) to 1.0 (very relevant).
     *
     * Calculate a score from 0.0 to 1.0 based on Levenshtein distance and vibes.
     * This is different from Meilisearch's relevance ranking and is complementary to it.
     * Meilisearch does a good job of ranking individual scope results by relevance, but
     * we also need to weigh the scopes themselves by the relevance of their internal entries
     * against the user's original query. This helps us know what scopes the user might care about most.
     *
     * This is only used to determine what order the scopes should be presented as in the UI.
     */
    public function execute(string $query, string $title): float
    {
        $normalizedQuery = mb_strtolower($query);
        $normalizedTitle = mb_strtolower($title);

        // Exact match gets a perfect score.
        if ($normalizedQuery === $normalizedTitle) {
            return 1.0;
        }

        // Use Levenshtein distance normalized by the longer string length.
        $distance = levenshtein($normalizedQuery, $normalizedTitle);
        $maxLength = max(mb_strlen($normalizedQuery), mb_strlen($normalizedTitle));

        // Convert distance to similarity (0 distance = 1.0 similarity).
        $similarity = 1.0 - ($distance / $maxLength);

        // Boost if title contains the exact query.
        if (str_contains($normalizedTitle, $normalizedQuery)) {
            $similarity = max($similarity, 0.7);
        }

        return max(0.0, $similarity);
    }
}
