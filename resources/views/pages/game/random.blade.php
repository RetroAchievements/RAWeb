<?php

declare(strict_types=1);

use App\Models\Game;
use App\Platform\Enums\AchievementFlag;

use function Laravel\Folio\{middleware, name, render};

middleware(['can:viewAny,' . Game::class]);
name('game.random');

// This seems to be the most efficient way to randomly pick a game with achievements.
// Each iteration of this loop takes less than 1ms, whereas alternate implementations that
// scanned the table using "LIMIT RAND(),1" or "ORDER BY RAND() LIMIT 1" took upwards of
// 400ms as the calculation for number of achievements had to be done for every row skipped.
// With 25k rows in the GameData table, and 6k games with achievements, the chance of any
// individual query failing is roughly 75%. The chance of three queries in a row failing is
// 42%. At ten queries, the chance is way down at 6%, and we're still 40+ times faster than
// the alternate solutions.
$getRandomGameWithAchievements = function(): Game {
    $maxId = Game::max('ID');

    do {
        $gameId = random_int(1, $maxId);
        $gameWithAchievements = Game::where('ID', $gameId)
            ->where('ConsoleID', '<', 100)
            ->where('achievements_published', '>', 0)
            ->first();
    } while (!$gameWithAchievements);

    return $gameWithAchievements;
};

render(function () use ($getRandomGameWithAchievements) {
    return redirect(route('game.show', ['game' => $getRandomGameWithAchievements()]));
});

?>
