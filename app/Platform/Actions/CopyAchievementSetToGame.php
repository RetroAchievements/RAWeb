<?php

declare(strict_types=1);

namespace App\Platform\Actions;

class CopyAchievementSetToGame
{
    public function execute(): void
    {
        /*
         * TODO
         * achievements may be copied over from another game:
         * - duplicate each achievement, with latest version (optional?)
         * - create new achievement_set and assign achievements
         *
         * - create new badge set composition
         * - duplicate badge set
         * - duplicate achievement_set and assign to game
         */
    }
}
