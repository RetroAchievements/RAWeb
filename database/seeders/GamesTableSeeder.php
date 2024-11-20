<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\System;
use App\Platform\Enums\AchievementFlag;
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
            $system->games()->saveMany(Game::factory()->count(3)->create(['ConsoleID' => $system->ID]));
        });

        /*
         * add hashes to games
         */
        Game::all()->each(function (Game $game) {
            $game->hashes()->save(new GameHash([
                'system_id' => $game->ConsoleID,
                'hash' => Str::random(32),
                'md5' => Str::random(32),
            ]));
        });

        /*
         * add achievements to games
         */
        Game::all()->each(function (Game $game) {
            $game->achievements()->saveMany(Achievement::factory()->count(random_int(0, 10))->create([
                'GameID' => $game->ID,
                'Flags' => AchievementFlag::OfficialCore->value,
            ]));
        });

        Game::all()->each(function (Game $game) {
            $leaderboardCount = random_int(0, 10);

            $leaderboards = Leaderboard::factory()->count($leaderboardCount)->make()->each(function (Leaderboard $leaderboard) use ($game) {
                $leaderboard->GameID = $game->ID;
                $leaderboard->Title = ucwords(fake()->words(2, true));
                $leaderboard->Description = fake()->sentence();
            });

            $game->leaderboards()->saveMany($leaderboards);
        });
    }
}
