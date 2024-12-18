<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;

class TrimGameMetadataAction
{
    public function execute(Game $game): void
    {
        $game->Title = $this->trimWhitespace($game->Title);
        $game->Publisher = $this->trimWhitespace($game->Publisher);
        $game->Developer = $this->trimWhitespace($game->Developer);
        $game->Genre = $this->trimWhitespace($game->Genre);
        $game->save();
    }

    // FIXME actions should only expose `execute()`
    public static function trimWhitespace(?string $toTrim): ?string
    {
        return trim(preg_replace('/\s+/', ' ', $toTrim ?? ''));
    }
}
