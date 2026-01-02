<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;

class TrimGameMetadataAction
{
    public function execute(Game $game): void
    {
        $game->title = $this->trimWhitespace($game->title);
        $game->publisher = $this->trimWhitespace($game->publisher);
        $game->developer = $this->trimWhitespace($game->developer);
        $game->genre = $this->trimWhitespace($game->genre);
        $game->save();
    }

    // FIXME actions should only expose `execute()`
    public static function trimWhitespace(?string $toTrim): ?string
    {
        return trim(preg_replace('/\s+/', ' ', $toTrim ?? ''));
    }
}
