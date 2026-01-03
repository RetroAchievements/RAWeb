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
use App\Platform\Enums\AchievementType;
use Carbon\Carbon;
use DateTime;
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
            $num_to_create = rand(0, 10) + rand(0, 2) + rand(0, 2) + rand(0, 2);
            $system->games()->saveMany(Game::factory()->count($num_to_create)->create(['system_id' => $system->id]));
        });

        /*
         * add hashes to games
         */
        Game::all()->each(function (Game $game) {
            $game->hashes()->save(new GameHash([
                'system_id' => $game->system_id,
                'hash' => Str::random(32),
                'md5' => Str::random(32),
            ]));
        });

        /*
         * add achievements to games
         */
        $developers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR, Role::DEVELOPER_RETIRED]);
        })->pluck('id')->toArray();

        Game::all()->each(function (Game $game) use ($developers, $faker) {
            if (!isValidConsoleId($game->system_id)) {
                // don't populate games for inactive systems
                return;
            }

            $date = Carbon::parse($faker->dateTimeBetween('-3 years', '-2 hours')->format(DateTime::ATOM));

            if (rand(0, 2) === 0) {
                // leave some games without achievements

                if (rand(0, 10) === 0) {
                    // small chance of only having unofficial achievements
                    $user_id = $developers[array_rand($developers)];

                    $game->achievements()->saveMany(Achievement::factory()->count(rand(1, 5))->create([
                        'game_id' => $game->id,
                        'is_promoted' => false,
                        'user_id' => $user_id,
                    ]));

                    $this->setAchievementCreationDates($game, $date);
                }

                return;
            }

            /* create published achievements */
            $user_id = $developers[array_rand($developers)];
            $game->achievements()->saveMany(Achievement::factory()->count(rand(5, 20))->create([
                'game_id' => $game->id,
                'is_promoted' => true,
                'user_id' => $user_id,
            ]));

            if (random_int(0, 100) <= 10) { // 10% chance to create unofficial achievements
                $game->achievements()->saveMany(Achievement::factory()->count(rand(0, 5))->create([
                    'game_id' => $game->id,
                    'is_promoted' => false,
                    'user_id' => $user_id,
                ]));
            }

            if (rand(0, 100) <= 5) { // 5% chance for another user to have also created acheivements in the set
                $user_id = $developers[array_rand($developers)];

                $game->achievements()->saveMany(Achievement::factory()->count(rand(0, 10))->create([
                    'game_id' => $game->id,
                    'is_promoted' => true,
                    'user_id' => $user_id,
                ]));
            }

            $this->setAchievementCreationDates($game, $date);

            /* assign display order and type */
            $num_achievements = $game->achievements()->promoted()->count();
            $num_progression = rand(3, max(3, (int) floor($num_achievements / 2)));
            $num_win = 1;
            if ($num_achievements > 7 && rand(1, 30) === 1) {
                $num_win++;

                if ($num_achievements > 13 && rand(1, 50) === 1) {
                    $num_win++;
                }
            }
            $num_remaining = $num_achievements - $num_progression - $num_win;
            $num_missable = rand(0, (int) floor($num_remaining / 3));
            $num_remaining -= $num_missable;

            $index = 1;
            foreach ($game->achievements()->promoted()->get() as $achievement) {
                $achievement->order_column = $index++;

                $type = rand(0, 2);
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

            $count = rand(-5, 10) + rand(-1, 2) + rand(-1, 2);
            if ($count > 0) {
                $game->leaderboards()->saveMany(Leaderboard::factory()->count($count)->create([
                    'game_id' => $game->id,
                    'author_id' => $user_id,
                    'created_at' => $date,
                ]));
            }
        });

        $gameMetricsAction = new UpdateGameMetricsAction();
        Game::all()->each(function (Game $game) use ($gameMetricsAction) {
            $gameMetricsAction->execute($game);

            if ($game->achievements_published > 0) {
                $set = $game->achievementSets()->first();
                $set->achievements_first_published_at = Carbon::parse($game->achievements()->promoted()->max('created_at'))->addSeconds(30, 3600);
                $set->save();
            }
        });
    }

    private function setAchievementCreationDates(Game $game, Carbon $date): void
    {
        foreach ($game->achievements()->get() as $achievement) {
            $achievement->created_at = $date;
            $achievement->save();

            switch (rand(0, 5)) {
                case 0:
                    $date = $date->addSeconds(rand(0, 100));
                    break;
                case 1:
                    $date = $date->addSeconds(rand(0, 10));
                    break;
                case 2:
                    $date = $date->addSeconds(rand(0, 1));
                    break;
            }
        }
    }
}
