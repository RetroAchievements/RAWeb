<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameSet;

class DetachGameLinksFromGameSetAction
{
    public function execute(GameSet $gameSet, array $parentGameSetIds): void
    {
        $gameSet->parents()->detach($parentGameSetIds);
        $gameSet->children()->detach($parentGameSetIds);
    }
}
