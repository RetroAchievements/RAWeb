<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameSet;

class AttachGameLinksToGameSetAction
{
    public function execute(GameSet $gameSet, array $parentGameSetIds): void
    {
        $gameSet->parents()->attach($parentGameSetIds);
        $gameSet->children()->attach($parentGameSetIds);
    }
}
