<?php

declare(strict_types=1);

namespace App\Filament\Actions;

class ParseIdsFromCsvAction
{
    /**
     * Parse a string containing IDs in various formats into an array of unique IDs.
     *
     * Supported formats:
     * - Comma-separated: "1,2,3"
     * - Space-separated: "1 2 3"
     * - Mixed separation: "1, 2 3"
     * - URLs: "retroachievements.org/game/1 game/2 hub/3".
     */
    public function execute(string $input): array
    {
        // Extract any trailing numbers from URLs or path segments.
        $cleanInput = preg_replace_callback(
            [
                // Match full URLs with trailing numbers.
                '/https?:\/\/[^\/]+(?:\/[^\/]+)*\/(\d+)\/?/',
                // Match any path segments with trailing numbers.
                '/(?:^|\s)\/[^\/\s]+\/(\d+)\/?/',
                // Catch any remaining path-like patterns with numbers.
                '/(?:^|\s)[^\/\s]+\/(\d+)\/?/',
            ],
            fn ($matches) => ' ' . $matches[1] . ' ',
            $input
        );

        // Split on commas and/or spaces.
        $parts = preg_split('/[,\s]+/', $cleanInput, -1, PREG_SPLIT_NO_EMPTY);

        // Filter to keep only numeric distinct values.
        return array_values(array_unique(
            array_filter($parts, fn ($part) => is_numeric($part))
        ));
    }
}
