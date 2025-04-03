<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GamesTableSeeder extends Seeder
{
    public function run(): void
    {
        if (Game::count() > 0) {
            return;
        }

        $faker = Faker::create();

        /*
         * add games to systems
         */
        System::all()->each(function (System $system) {
            $num_to_create = random_int(0, 10) + random_int(0, 2) + random_int(0, 2) + random_int(0, 2);
            $system->games()->saveMany(Game::factory()->count($num_to_create)->create(['ConsoleID' => $system->ID]));
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
        $developers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR, Role::DEVELOPER_RETIRED]);
        })->pluck('ID')->toArray();

        Game::all()->each(function (Game $game) use ($developers, $faker) {
            // GameFactory appends a sequence number to every title to ensure uniqueness.
            // Generate a new title using sequel numbering to ensure uniqueness.
            $newTitle = ucwords($faker->words(random_int(1, 4), true));
            if (Game::where('Title', $newTitle)->exists()) {
                $index = 2;
                while (true) {
                    $testTitle = $newTitle . ' ' . match ($index) {
                        2 => 'II',
                        3 => 'III',
                        4 => 'IV',
                        5 => 'V',
                        6 => 'VI',
                        7 => 'VII',
                        default => strval($index),
                    };

                    if (!Game::where('Title', $testTitle)->exists()) {
                        $newTitle = $testTitle;
                        break;
                    }
                    $index++;
                }
            }
            $game->Title = $newTitle;
            $game->save();

            if (!isValidConsoleId($game->ConsoleID)) {
                // don't populate games for inactive systems
                return;
            }

            if (random_int(0, 2) === 0) {
                // leave some games without achievements

                if (random_int(0, 10) === 0) {
                    // small chance of only having unofficial achievements
                    $user_id = $developers[array_rand($developers)];

                    $game->achievements()->saveMany(Achievement::factory()->count(random_int(1, 5))->create([
                        'GameID' => $game->ID,
                        'Flags' => AchievementFlag::Unofficial->value,
                        'user_id' => $user_id,
                    ]));
                }

                return;
            }

            /* create published achievements */
            $user_id = $developers[array_rand($developers)];
            $game->achievements()->saveMany(Achievement::factory()->count(random_int(5, 20))->create([
                'GameID' => $game->ID,
                'Flags' => AchievementFlag::OfficialCore->value,
                'user_id' => $user_id,
            ]));

            if (random_int(0, 100) <= 10) { // 10% chance to create unofficial achievements
                $game->achievements()->saveMany(Achievement::factory()->count(random_int(0, 5))->create([
                    'GameID' => $game->ID,
                    'Flags' => AchievementFlag::Unofficial->value,
                    'user_id' => $user_id,
                ]));
            }

            if (random_int(0, 100) <= 5) { // 5% chance for another user to have also created acheivements in the set
                $user_id = $developers[array_rand($developers)];

                $game->achievements()->saveMany(Achievement::factory()->count(random_int(0, 10))->create([
                    'GameID' => $game->ID,
                    'Flags' => AchievementFlag::OfficialCore->value,
                    'user_id' => $user_id,
                ]));
            }

            /* assign display order and type */
            $num_achievements = $game->achievements()->published()->count();
            $num_progression = random_int(3, max(3, (int) floor($num_achievements / 2)));
            $num_win = 1;
            if ($num_achievements > 7 && random_int(1, 30) === 1) {
                $num_win++;

                if ($num_achievements > 13 && random_int(1, 50) === 1) {
                    $num_win++;
                }
            }
            $num_remaining = $num_achievements - $num_progression - $num_win;
            $num_missable = random_int(0, (int) floor($num_remaining / 3));
            $num_remaining -= $num_missable;

            $index = 1;
            foreach ($game->achievements()->published()->get() as $achievement) {
                $achievement->DisplayOrder = $index++;

                $type = random_int(0, 2);
                  while (true) {
                    $valid = match ($type) {
                        0 => ($num_remaining > 0),
                        1 => ($num_missable > 0),
                        default => true,
                    };

                    if ($valid) {
                        break;
                    }

                    $type++;
                }

                if ($type === 1) {
                    $achievement->type = AchievementType::Missable;
                    $num_missable--;
                } elseif ($type === 2) {
                    if ($num_progression > 0) {
                        $achievement->type = AchievementType::Progression;
                        $num_progression--;
                    } elseif ($num_win > 0) {
                        $achievement->type = AchievementType::WinCondition;
                        $num_win--;
                    }
                }
                $achievement->save();
            }
        });

        Game::all()->each(function (Game $game) use ($developers) {
            $user_id = $game->achievements()->first()->user_id ?? $developers[array_rand($developers)];

            $game->leaderboards()->saveMany(Leaderboard::factory()->count(random_int(0, 10))->create([
                'GameID' => $game->ID,
                'author_id' => $user_id,
            ]));
        });

        $gameMetricsAction = new UpdateGameMetricsAction();
        Game::all()->each(function (Game $game) use ($gameMetricsAction) {
            $gameMetricsAction->execute($game);
        });
    }
}
