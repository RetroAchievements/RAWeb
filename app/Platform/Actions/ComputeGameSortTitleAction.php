<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use Transliterator;

class ComputeGameSortTitleAction
{
    public function execute(string $gameTitle): string
    {
        $sortTitle = $this->replaceRomanNumerals($gameTitle);
        $sortTitle = $this->padNumbers($sortTitle);
        $sortTitle = $this->removeArticles($sortTitle);
        $sortTitle = mb_strtolower($sortTitle);
        $sortTitle = $this->normalizeAccents($sortTitle);
        $sortTitle = $this->stripPunctuation($sortTitle);
        $sortTitle = $this->fixTagTildes($sortTitle);

        return $sortTitle;
    }

    /**
     * Pad Arabic numbers with leading zeroes to ensure proper sorting.
     *
     * eg: "Mega Man 10" should not be sorted before "Mega Man 2". With the sort titles
     * set to "mega man 00010" and "mega man 00002" respectively, we can mitigate this.
     */
    private function padNumbers(string $title): string
    {
        return preg_replace_callback(
            // Match numbers not directly attached to letters.
            // In other words, don't convert "Mega Man X4" to "Mega Man X00004".
            '/(?<=\b|\s)(\d+)(?=\b|\s)/',
            function ($matches) {
                return str_pad($matches[0], 5, '0', STR_PAD_LEFT);
            },
            $title
        );
    }

    /**
     * Replace Roman numerals with their corresponding padded numeric
     * equivalents (eg: "IV" -> "04").
     */
    private function replaceRomanNumerals(string $title): string
    {
        $romanNumerals = [
            'I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5,
            'VI' => 6, 'VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10,
            'XI' => 11, 'XII' => 12, 'XIII' => 13, 'XIV' => 14, 'XV' => 15,
            'XVI' => 16, 'XVII' => 17, 'XVIII' => 18, 'XIX' => 19, 'XX' => 20,
        ];

        /**
         * Valid conversions are at the end of a string, or followed by
         * non-apostrophe punctuation (or whitespace then punctuation) like :, -, &, or (.
         */
        $title = preg_replace_callback(
            '/\b([IVX]+)(?=(?:$|\s*[:\-&\(\)]|\s*$))/i',
            function ($matches) use ($romanNumerals) {
                $roman = $matches[1];
                if (isset($romanNumerals[$roman])) {
                    return sprintf('%02d', $romanNumerals[$roman]);
                }

                return $roman;
            },
            $title
        );

        return $title;
    }

    /**
     * Removing leading and trailing articles.
     *
     * "The Matrix" -> "Matrix"
     * "A Bug's Life" -> "Bug's Life"
     * "Legend of Zelda, The" -> "Legend of Zelda"
     */
    private function removeArticles(string $title): string
    {
    // Remove articles at the start of the title
        if (preg_match('/^\s*(a|an|the)\b\s*(.*)$/i', $title, $matches)) {
            $title = $matches[2];
        }

        // Remove articles at the end after a comma
        if (preg_match('/^(.+),\s*(a|an|the)\b(.*)$/i', $title, $matches)) {
            // Combine the main title and any trailing text.
            $title = trim($matches[1] . $matches[3]);
        }

        return $title;
    }

    /**
     * "Pokémon Stadium" -> "Pokemon Stadium"
     */
    private function normalizeAccents(string $title): string
    {
        return Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')->transliterate($title);
    }

    /**
     * "Luigi's Mansion" -> "Luigis Mansion"
     * "The Legend of Zelda: Link's Awakening" -> "The Legend of Zelda Links Awakening"
     */
    private function stripPunctuation(string $title): string
    {
        // Keep tildes (~) and hyphens (-).
        return preg_replace("/[^\w\s~\-]+/u", '', $title);
    }

    /**
     * For titles starting with "~", the sort order is determined by the content
     * within the "~" markers followed by the content after the "~". This ensures
     * that titles with "~" are grouped together and sorted alphabetically based
     * on their designated categories and then by their actual game title.
     */
    private function fixTagTildes(string $title): string
    {
        if ($title[0] === '~') {
            $endOfFirstTilde = strpos($title, '~', 1);
            if ($endOfFirstTilde !== false) {
                $withinTildes = substr($title, 1, $endOfFirstTilde - 1);
                $afterTildes = trim(substr($title, $endOfFirstTilde + 1));

                $title = '~' . $withinTildes . ' ' . $afterTildes;
            }
        }

        return $title;
    }
}
