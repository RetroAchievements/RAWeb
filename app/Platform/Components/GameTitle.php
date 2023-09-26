<?php

declare(strict_types=1);

namespace App\Platform\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GameTitle extends Component
{
    public string $rawTitle = '';
    public bool $showTags = true;

    public function __construct(string $rawTitle, bool $showTags = true)
    {
        $this->rawTitle = $rawTitle;
        $this->showTags = $showTags;
    }

    public function render(): View
    {
        $strippedTitleAndTags = $this->stripTagsFromTitle($this->rawTitle);

        return view('platform.components.game.title', [
            'showTags' => $this->showTags,
            'nonSubsetTags' => $strippedTitleAndTags['nonSubsetTags'],
            'strippedTitle' => $strippedTitleAndTags['strippedTitle'],
            'subsetKind' => $strippedTitleAndTags['subsetKind'],
        ]);
    }

    private function stripTagsFromTitle(string $rawTitle): array
    {
        $subsetKind = null;
        $nonSubsetTags = [];

        $decodedTitle = html_entity_decode($rawTitle, ENT_QUOTES, 'UTF-8');
        $strippedTitle = $decodedTitle;

        // Use a single regex operation to extract all tags in the format ~Tag~.
        if (preg_match_all('/~([^~]+)~/', $decodedTitle, $rawMatches)) {
            foreach ($rawMatches[1] as $match) {
                $sanitized = htmlspecialchars($match, ENT_NOQUOTES, 'UTF-8');
                $nonSubsetTags[] = $sanitized;
            }

            // Remove all ~Tag~ instances from the title.
            $strippedTitle = preg_replace('/~[^~]+~/', '', $strippedTitle);
        }

        // Use a single regex operation to extract the subset.
        if (preg_match('/\s?\[Subset - (.+)\]/', $decodedTitle, $subsetMatches)) {
            $subsetKind = htmlspecialchars($subsetMatches[1], ENT_NOQUOTES, 'UTF-8');

            // Remove the subset tag from the title.
            $strippedTitle = preg_replace('/\s?\[Subset - .+\]/', '', $strippedTitle);
        }

        $strippedTitle = trim($strippedTitle);

        return [
            'subsetKind' => $subsetKind,
            'nonSubsetTags' => $nonSubsetTags,
            'strippedTitle' => $strippedTitle,
        ];
    }
}
