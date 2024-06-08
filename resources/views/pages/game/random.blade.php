<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\System;
use App\Platform\Enums\AchievementFlag;

use function Laravel\Folio\{middleware, name, render};

middleware(['can:viewAny,' . Game::class]);
name('game.random');

render(function () {
    $randomGameWithAchievements = Game::whereNotIn('ConsoleID', System::getNonGameSystems())
        ->where('achievements_published', '>=', 6)
        ->inRandomOrder()
        ->firstOrFail();

    return redirect(route('game.show', ['game' => $randomGameWithAchievements]));
});

?>
