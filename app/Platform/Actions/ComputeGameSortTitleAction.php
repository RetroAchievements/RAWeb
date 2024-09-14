<?php

declare(strict_types=1);

namespace App\Platform\Actions;

class ComputeGameSortTitleAction
{
    public function execute(string $gameTitle): string
    {
        $sortTitle = $this->replaceRomanNumerals($gameTitle);
        $sortTitle = $this->removeArticles($sortTitle);
        $sortTitle = mb_strtolower($sortTitle);
        $sortTitle = $this->fixTagTildes($sortTitle);

        return $sortTitle;
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

        $title = preg_replace_callback(
            '/\b([IVX]+)\b/',
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
     * "Legend of Zelda, The: A Link to the Past" -> "Legend of Zelda: A Link to the Past"
     *
     * ", The" has been removed.
     */
    private function removeArticles(string $title): string
    {
        if (preg_match('/^(.+),\s*(a|an|the)\b(.*)$/i', $title, $matches)) {
            // Combine the main title and any trailing text.
            $mainTitle = trim($matches[1] . $matches[3]);

            return $mainTitle;
        }

        return $title;
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
