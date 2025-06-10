<?php

declare(strict_types=1);

namespace App\Platform\Actions;

/**
 * $systemName and $systemNameShort are passed in as separate string
 * args so we don't need to worry about instantiating real `System`
 * objects during tests.
 */

class ComputeGameSearchTitlesAction
{
    /**
     * Map of Roman numerals to their numeric equivalents.
     * Limited to 1-20, which should cover most common game series.
     */
    private const ROMAN_NUMERAL_MAP = [
        'I' => '1', 'II' => '2', 'III' => '3', 'IV' => '4', 'V' => '5',
        'VI' => '6', 'VII' => '7', 'VIII' => '8', 'IX' => '9', 'X' => '10',
        'XI' => '11', 'XII' => '12', 'XIII' => '13', 'XIV' => '14', 'XV' => '15',
        'XVI' => '16', 'XVII' => '17', 'XVIII' => '18', 'XIX' => '19', 'XX' => '20',
    ];

    public function execute(
        string $gameTitle,
        string $systemName,
        string $systemNameShort,
        array $altTitles = []
    ): array {
        $searchTitles = [];

        // Process the main title first.
        $mainTitleVariations = $this->generateSearchVariationsForTitle($gameTitle);
        $searchTitles = array_merge($searchTitles, $mainTitleVariations);

        // Then, process each alternative title.
        foreach ($altTitles as $altTitle) {
            $altTitleVariations = $this->generateSearchVariationsForTitle($altTitle);
            $searchTitles = array_merge($searchTitles, $altTitleVariations);
        }

        // Then, add system-based variations so searches such as "donkey kong arcade" return relevant results.
        $systemVariations = $this->generateSystemVariations($gameTitle, $altTitles, $systemName, $systemNameShort);
        $searchTitles = array_merge($searchTitles, $systemVariations);

        // Finally, remove duplicates and empty values to keep the index clean.
        $searchTitles = array_values(array_unique(array_filter($searchTitles)));

        return $searchTitles;
    }

    /**
     * Generate all search variations for a single title.
     *
     * This creates multiple versions of the title to handle common search patterns,
     * including case variations, numeral conversions, abbreviations, and simplified forms.
     */
    private function generateSearchVariationsForTitle(string $title): array
    {
        $variations = [];

        // Always include the original title as-is.
        $variations[] = $title;

        // Add lowercase for case-insensitive matching.
        $variations[] = mb_strtolower($title);

        // Convert Roman numerals to numbers for searches like "final fantasy 7".
        $titleWithNumbers = $this->convertRomanNumeralsToNumbers($title);
        if ($titleWithNumbers !== $title) {
            $variations[] = $titleWithNumbers;
            $variations[] = mb_strtolower($titleWithNumbers);
        }

        // Convert numbers to Roman numerals for searches like "final fantasy vii".
        $titleWithRomanNumerals = $this->convertNumbersToRomanNumerals($title);
        if ($titleWithRomanNumerals !== $title) {
            $variations[] = $titleWithRomanNumerals;
            $variations[] = mb_strtolower($titleWithRomanNumerals);
        }

        // Generate common abbreviations like "ff7" or "sm64".
        $abbreviations = $this->generateAbbreviations($title);
        foreach ($abbreviations as $abbreviation) {
            $variations[] = $abbreviation;
        }

        // Simple check for Professor Oak Challenge subset.
        if (stripos($title, 'Professor Oak Challenge') !== false) {
            $variations[] = 'poc';
        }
        // OoT is another popular query.
        if (stripos($title, 'Ocarina of Time') !== false) {
            $variations[] = 'oot';
        }

        // Create simplified titles that don't have any special characters.
        $simplifiedTitle = $this->simplifyTitle($title);
        if ($simplifiedTitle !== $title) {
            $variations[] = $simplifiedTitle;
        }

        // Handle titles starting with "The" by creating a version without it.
        if (preg_match('/^The\s+(.+)$/i', $title, $matches)) {
            $variations[] = $matches[1];
            $variations[] = mb_strtolower($matches[1]);
        }

        return $variations;
    }

    /**
     * Convert Roman numerals in titles to their numeric equivalents.
     *
     * This allows users to search for "Final Fantasy 7" and find "Final Fantasy VII".
     * Only converts numerals that are word boundaries to avoid false positives.
     */
    private function convertRomanNumeralsToNumbers(string $title): string
    {
        // Need to reverse the order for regex to match longer numerals first (XX before X).
        $romanNumerals = array_reverse(self::ROMAN_NUMERAL_MAP, true);

        // Only replace Roman numerals that are complete words.
        return preg_replace_callback(
            '/\b(' . implode('|', array_keys($romanNumerals)) . ')\b/i',
            function ($matches) {
                $romanNumeral = strtoupper($matches[1]);

                return self::ROMAN_NUMERAL_MAP[$romanNumeral] ?? $romanNumeral;
            },
            $title
        );
    }

    /**
     * Convert numbers in titles to their Roman numeral equivalents.
     *
     * This allows users to search for "Final Fantasy VII" and find "Final Fantasy 7".
     * Limited to numbers 1-20 which covers most common game series.
     */
    private function convertNumbersToRomanNumerals(string $title): string
    {
        // Flip the map to go from numbers to Roman numerals (eg: '7' -> 'VII').
        $numberToRomanMap = array_flip(self::ROMAN_NUMERAL_MAP);

        return preg_replace_callback(
            '/\b(' . implode('|', array_keys($numberToRomanMap)) . ')\b/',
            function ($matches) use ($numberToRomanMap) {
                return $numberToRomanMap[$matches[1]] ?? $matches[1];
            },
            $title
        );
    }

    /**
     * Generate common abbreviations for game titles.
     *
     * Creates shortened versions like "FF7" for "Final Fantasy VII" or "SM64" for
     * "Super Mario 64". This handles the common way users search for games using
     * series abbreviations combined with numbers.
     */
    private function generateAbbreviations(string $title): array
    {
        $abbreviations = [];

        // Map of series names to their common abbreviations.
        // It doesn't matter if some of these overlap, and these aren't case-sensitive.
        // This is just a way we can influence how heavily Meilisearch weighs certain terms.
        $seriesAbbreviationMap = [
            'Breath of Fire' => ['BoF'],
            'Call of Duty' => ['CoD'],
            'Castlevania' => ['CV'],
            'Devil May Cry' => ['DMC'],
            'Donkey Kong' => ['DK'],
            'Dragon Quest' => ['DQ'],
            'Dragon Warrior' => ['DW'],
            'Final Fantasy' => ['FF'],
            'God of War' => ['GoW'],
            'Gran Turismo' => ['GT'],
            'Grand Theft Auto' => ['GTA'],
            'King of Fighters' => ['KOF'],
            'Kingdom Hearts' => ['KH'],
            'Legend of Zelda' => ['LoZ', 'TLoZ'],
            'Mega Man' => ['MM'],
            'Metal Gear Solid' => ['MGS'],
            'Mortal Kombat' => ['MK'],
            'Need for Speed' => ['NFS'],
            'Resident Evil' => ['RE'],
            'Shin Megami Tensei' => ['SMT'],
            'Silent Hill' => ['SH'],
            'Street Fighter' => ['SF'],
            'Super Mario' => ['SM'],
        ];

        // Check each series for matches.
        foreach ($seriesAbbreviationMap as $seriesName => $seriesAbbreviations) {
            if (stripos($title, $seriesName) !== false) {
                // Look for numbers or Roman numerals after the series name.
                $pattern = '/' . preg_quote($seriesName, '/') . '\s*(\d+|[IVX]+)?\b/i';
                if (preg_match($pattern, $title, $matches)) {
                    foreach ($seriesAbbreviations as $abbreviation) {
                        if (isset($matches[1])) {
                            // Add abbreviation with the number/numeral.
                            $abbreviations[] = mb_strtolower($abbreviation . $matches[1]);
                            $abbreviations[] = mb_strtolower($abbreviation . ' ' . $matches[1]);

                            // Convert between Roman numerals and numbers for more variations.
                            $this->addNumericVariations($abbreviation, $matches[1], $abbreviations);
                        }
                        // Always include just the base abbreviation.
                        $abbreviations[] = mb_strtolower($abbreviation);
                    }
                }
            }
        }

        // Handle games ending with numbers.
        $this->handleNumericSuffixAbbreviations($title, $seriesAbbreviationMap, $abbreviations);

        return array_unique($abbreviations);
    }

    /**
     * Add numeric variations of abbreviations.
     *
     * Converts between Roman numerals and Arabic numbers to create more search variations.
     * For example, "FFVII" generates "FF7" and vice versa.
     */
    private function addNumericVariations(string $abbreviation, string $numberOrNumeral, array &$abbreviations): void
    {
        // If it's a Roman numeral, add the number version.
        if (isset(self::ROMAN_NUMERAL_MAP[strtoupper($numberOrNumeral)])) {
            $number = self::ROMAN_NUMERAL_MAP[strtoupper($numberOrNumeral)];
            $abbreviations[] = mb_strtolower($abbreviation . $number);
        }

        // If it's a number, add the Roman numeral version.
        // Flip the map to go from numbers to Roman numerals (eg: '7' -> 'VII').
        $numberToRomanMap = array_flip(self::ROMAN_NUMERAL_MAP);
        if (isset($numberToRomanMap[$numberOrNumeral])) {
            $romanNumeral = $numberToRomanMap[$numberOrNumeral];
            $abbreviations[] = mb_strtolower($abbreviation . mb_strtolower($romanNumeral));
        }
    }

    /**
     * Generate search variations that include the system name.
     *
     * This allows users to search with system names like "donkey kong arcade"
     * to find games from specific systems.
     */
    private function generateSystemVariations(
        string $gameTitle,
        array $altTitles,
        string $systemName,
        string $systemNameShort
    ): array {
        $variations = [];

        // Process main title and alt titles.
        $titles = array_merge([$gameTitle], $altTitles);

        foreach ($titles as $title) {
            // Add a variation with the standard system name.
            $variations[] = mb_strtolower($title . ' ' . $systemName);

            // Add a variation with the short system name (if it's different from full name).
            if (mb_strtolower($systemNameShort) !== mb_strtolower($systemName)) {
                $variations[] = mb_strtolower($title . ' ' . $systemNameShort);
            }
        }

        return array_unique($variations);
    }

    /**
     * Handle abbreviations for games ending with numbers.
     *
     * Creates variations like "MM2" for "Mega Man 2" by combining series
     * abbreviations with numeric suffixes.
     */
    private function handleNumericSuffixAbbreviations(
        string $title,
        array $seriesAbbreviationMap,
        array &$abbreviations
    ): void {
        if (!preg_match('/^(.+?)\s+(\d+)$/', $title, $matches)) {
            return;
        }

        $gameName = trim($matches[1]);
        $number = $matches[2];

        // Check if we have an abbreviation for this game series.
        foreach ($seriesAbbreviationMap as $seriesName => $seriesAbbreviations) {
            if (stripos($gameName, $seriesName) !== false) {
                foreach ($seriesAbbreviations as $abbreviation) {
                    $abbreviations[] = mb_strtolower($abbreviation . $number);
                }
            }
        }
    }

    /**
     * Simplify title by removing special characters and normalizing whitespace.
     *
     * This creates a clean version of the title for users who might not include
     * punctuation in their searches. For example, "Luigi's Mansion" becomes
     * "luigis mansion" for easier matching.
     */
    private function simplifyTitle(string $title): string
    {
        // Remove all non-alphanumeric characters except spaces.
        $simplified = preg_replace('/[^\w\s]+/', ' ', $title);

        // Collapse multiple spaces into single spaces.
        $simplified = preg_replace('/\s+/', ' ', $simplified);

        return mb_strtolower(trim($simplified));
    }
}
