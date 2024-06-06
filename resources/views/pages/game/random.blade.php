<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\System;
use App\Platform\Enums\AchievementFlag;

use function Laravel\Folio\{middleware, name, render};

middleware(['can:viewAny,' . Game::class]);
name('game.random');

render(function () {
    $randomGameWithAchievements = Game::where('ConsoleID', '<', 100)
        ->whereNotIn('ConsoleID', System::getNonGameSystems())
        ->inRandomOrder()
        ->firstOrFail();

    return redirect(route('game.show', ['game' => $randomGameWithAchievements]));
});

?>
