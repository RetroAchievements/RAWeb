<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use Exception;
use Illuminate\Support\Carbon;

class ConvertGameReleasedToTimestamp
{
    public function execute(Game $game): array
    {
        // [timestamp, granularity]
        if (!$game->Released) {
            return [null, null];
        }

        $timestamp = $this->convertReleasedStringToTimestamp(
            $game->Released,
            $granularity,
        );
        if ($timestamp) {
            $game->released_at = $timestamp;
            $game->released_at_granularity = $granularity;
            $game->save();
        }

        return [$timestamp, $granularity];
    }

    private function convertReleasedStringToTimestamp(
        string $releasedString,
        ?string &$granularity,
    ): ?string {
        try {
            // Remove ordinal suffixes.
            $withoutCommas = str_replace(",", "", $releasedString);
            $withoutOrdinals = preg_replace(
                "/(\d)(st|nd|rd|th)\b/",
                '$1',
                $withoutCommas,
            );

            // Normalize the string by removing extra commas.
            $normalizedString = str_replace(",", "", $withoutOrdinals);

            // Handle different date formats.
            $formats = [
                "Y", // Year only
                "F Y", // Month and Year
                "F j Y", // Full date without commas
                "F d Y", // Full date with leading zero day
                "F j, Y", // Full date with comma
                "F d, Y", // Full date with leading zero day and comma
                "M Y", // Abbreviated month and year
                "M j Y", // Abbreviated month, day, and year without comma
                "M j, Y", // Abbreviated month, day, and year with comma
                "M d Y", // Abbreviated month, leading zero day, and year without comma
                "M d, Y", // Abbreviated month, leading zero day, and year with comma
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat(
                        $format,
                        $normalizedString,
                    );
                    if ($date) {
                        switch ($format) {
                            case "Y":
                                $granularity = "year";
                                $date = $date->startOfYear();
                                break;

                            case "F Y":
                            case "M Y":
                                $granularity = "month";
                                $date = $date->startOfMonth();
                                break;

                            default:
                                $granularity = "day";
                                break;
                        }

                        if ($date->year >= 1970) {
                            return $date->toDateTimeString();
                        }
                    }
                } catch (Exception $e) {
                    // Continue to the next format if parsing fails.
                }
            }

            // Try general parsing as a fallback.
            $date = Carbon::parse($normalizedString);
            if ($date->year >= 1970) {
                $granularity = "day";

                return $date->toDateTimeString();
            }
        } catch (Exception) {
            return null;
        }

        return null;
    }
}
