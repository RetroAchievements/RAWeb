<?php

declare(strict_types=1);

namespace App\Platform\Actions;

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

    public function execute(string $gameTitle, array $altTitles = []): array
    {
        $searchTitles = [];

        // Process the main title first.
        $mainTitleVariations = $this->generateSearchVariationsForTitle($gameTitle);
        $searchTitles = array_merge($searchTitles, $mainTitleVariations);

        // Then, process each alternative title.
        foreach ($altTitles as $altTitle) {
            $altTitleVariations = $this->generateSearchVariationsForTitle($altTitle);
            $searchTitles = array_merge($searchTitles, $altTitleVariations);
        }

        // Remove duplicates and empty values to keep the index clean.
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

        // Extract series names for partial matching like "zelda" or "mario".
        $seriesNames = $this->extractSeriesNames($title);
        foreach ($seriesNames as $seriesName) {
            $variations[] = $seriesName;
        }

        // Simple check for Professor Oak Challenge subset.
        if (stripos($title, 'Professor Oak Challenge') !== false) {
            $variations[] = 'poc';
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
            'Call of Duty' => ['CoD'],
            'Castlevania' => ['CV'],
            'Devil May Cry' => ['DMC'],
            'Donkey Kong' => ['DK'],
            'Dragon Quest' => ['DQ'],
            'Dragon Warrior' => ['DW'],
            'F-Zero' => ['FZ'],
            'Final Fantasy' => ['FF'],
            'God of War' => ['GoW'],
            'Grand Theft Auto' => ['GTA'],
            'Kingdom Hearts' => ['KH'],
            'Legend of Zelda' => ['Zelda', 'LoZ'],
            'Mega Man' => ['MM'],
            'Metal Gear' => ['MG'],
            'Mortal Kombat' => ['MK'],
            'Need for Speed' => ['NFS'],
            'Resident Evil' => ['RE'],
            'Silent Hill' => ['SH'],
            'Star Fox' => ['SF'],
            'Street Fighter' => ['SF'],
            'Super Mario' => ['SM'],
            'The Legend of Zelda' => ['Zelda', 'LoZ', 'TLoZ'],
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

        // Special handling for Nintendo 64 games.
        $this->handleNintendo64Abbreviations($title, $abbreviations);

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
     * Handle special abbreviations for Nintendo 64 games.
     *
     * Creates variations like "SM64" for "Super Mario 64" and handles the common
     * pattern of removing spaces before "64" in search queries.
     */
    private function handleNintendo64Abbreviations(string $title, array &$abbreviations): void
    {
        if (!preg_match('/\b64\b/', $title)) {
            return;
        }

        // Create version without spaces before "64".
        $titleNoSpace64 = preg_replace('/\s+64\b/', '64', $title);
        if ($titleNoSpace64 !== $title) {
            $abbreviations[] = mb_strtolower($titleNoSpace64);
        }

        // Create initials version for games ending in 64.
        $titleWithout64 = preg_replace('/\s*64\s*/', '', $title);
        $words = preg_split('/\s+/', $titleWithout64);
        $initials = '';

        foreach ($words as $word) {
            // Skip common words and special characters.
            $skipWords = ['the', 'of', 'and', 'in', 'at', 'to', 'a', '~hack~', '~homebrew~'];
            if (strlen($word) > 0 && !in_array(mb_strtolower($word), $skipWords)) {
                if (!preg_match('/[^a-zA-Z0-9]/', $word)) {
                    $initials .= mb_strtoupper(mb_substr($word, 0, 1));
                }
            }
        }

        if ($initials && strlen($initials) > 1) {
            $abbreviations[] = mb_strtolower($initials . '64');
        }

        // Hardcoded special case for Super Mario 64.
        if (stripos($title, 'Super Mario 64') !== false) {
            $abbreviations[] = 'sm64';
        }
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
     * Extract series names from game titles to improve search relevance.
     *
     * This method identifies and extracts base series names from full game titles,
     * allowing users to find games by searching just the series name. For example,
     * users often search for "zelda" instead of "The Legend of Zelda", or "final fantasy"
     * instead of the full title. By explicitly adding these series names as search terms,
     * we ensure better search results when users search by series.
     */
    private function extractSeriesNames(string $title): array
    {
        $seriesNames = [];

        // Map of title patterns to their common search terms.
        // This was picked very naively - we can expand this at any time.
        $seriesPatternMap = [
            '/^(Assassin\'s Creed)\s*/i' => 'assassins creed',
            '/^(Call of Duty)\s*/i' => 'call of duty',
            '/^(Castlevania)\s*/i' => 'castlevania',
            '/^(Devil May Cry)\s*/i' => 'devil may cry',
            '/^(Donkey Kong)\s*/i' => 'donkey kong',
            '/^(Dragon Quest)\s*/i' => 'dragon quest',
            '/^(Dragon Warrior)\s*/i' => 'dragon warrior',
            '/^(Elder Scrolls)\s*/i' => 'elder scrolls',
            '/^(F-Zero)\s*/i' => 'f-zero',
            '/^(Final Fantasy)\s*/i' => 'final fantasy',
            '/^(Fire Emblem)\s*/i' => 'fire emblem',
            '/^(God of War)\s*/i' => 'god of war',
            '/^(Grand Theft Auto)\s*/i' => 'grand theft auto',
            '/^(Halo)\s*/i' => 'halo',
            '/^(Kingdom Hearts)\s*/i' => 'kingdom hearts',
            '/^(Kirby)\s*/i' => 'kirby',
            '/^(Legend of Zelda)[:;]?\s*/i' => 'zelda',
            '/^(Mario Kart)\s*/i' => 'mario kart',
            '/^(Mario Party)\s*/i' => 'mario party',
            '/^(Mega Man)\s*/i' => 'mega man',
            '/^(Metal Gear)\s*/i' => 'metal gear',
            '/^(Metroid)\s*/i' => 'metroid',
            '/^(Mortal Kombat)\s*/i' => 'mortal kombat',
            '/^(Pokemon|Pokémon)\s*/i' => 'pokemon',
            '/^(Resident Evil)\s*/i' => 'resident evil',
            '/^(Silent Hill)\s*/i' => 'silent hill',
            '/^(Sonic the Hedgehog)\s*/i' => 'sonic',
            '/^(Sonic)\s*/i' => 'sonic',
            '/^(Star Fox)\s*/i' => 'star fox',
            '/^(Street Fighter)\s*/i' => 'street fighter',
            '/^(Super Mario)\s*/i' => 'super mario',
            '/^(The Elder Scrolls)\s*/i' => 'elder scrolls',
            '/^(The Legend of Zelda)[:;]?\s*/i' => 'zelda',
            '/^(Zelda)\s*/i' => 'zelda',
        ];

        foreach ($seriesPatternMap as $pattern => $searchTerm) {
            if (preg_match($pattern, $title)) {
                $seriesNames[] = mb_strtolower($searchTerm);
            }
        }

        return array_unique($seriesNames);
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
