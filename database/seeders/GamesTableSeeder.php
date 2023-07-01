<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\GameHashSet;
use App\Platform\Models\System;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GamesTableSeeder extends Seeder
{
    public function run(): void
    {
        if (Game::count() > 0) {
            return;
        }

        /*
         * add games to systems
         */
        System::all()->each(function (System $system) {
            $system->games()->saveMany(Game::factory()->count(3)->create());
        });

        /*
         * add hashes to games
         */
        Game::all()->each(function (Game $game) {
            $game->gameHashSets()->save(new GameHashSet([
                'game_id' => $game->id,
            ]))->hashes()->save(new GameHash([
                'system_id' => $game->system_id,
                'hash' => Str::random(16),
            ]));
        });

        /*
         * add achievements to games
         */
        Game::all()->each(function (Game $game) {
            $game->achievements()->saveMany(Achievement::factory()->count(random_int(0, 10))->create());
        });
    }
}
