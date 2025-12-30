<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Actions\UpdateDeveloperContributionYieldAction;
use App\Platform\Actions\UpdateGameAchievementsMetricsAction;
use App\Platform\Actions\UpdateGameBeatenMetricsAction;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Actions\UpdateGamePlayerCountAction;
use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Actions\UpdatePlayerMetricsAction;
use App\Platform\Enums\AchievementType;
use DateTime;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PlayerAchievementsSeeder extends Seeder
{
    public function run(): void
    {
        $maxPlayers = (int) floor(User::count() / 2);
        $faker = Faker::create();

        Game::where('achievements_published', '>', 0)->each(function (Game $game) use ($maxPlayers, $faker) {
            $numWinConditions = $game->achievements()->promoted()->where('type', AchievementType::WinCondition)->count();
            $numAchievements = $game->achievements()->promoted()->count();

            $updatePlayerGameMetricsAction = new UpdatePlayerGameMetricsAction();
            $resumePlayerSessionAction = new ResumePlayerSessionAction();

            $set = $game->achievementSets()->first();

            $numPlayers = (int) sqrt(rand(1, $maxPlayers * $maxPlayers));
            foreach (User::inRandomOrder()->limit($numPlayers)->get() as $user) {
                if ($user->points_hardcore > 0 && rand(0, $user->points_hardcore) < 10) {
                    // small chance of player loading game without earning any achievements
                    continue;
                }

                $date = Carbon::parse($faker->dateTimeBetween($user->created_at, '-2 hours')->format(DateTime::ATOM));
                if ($date < $set->achievements_first_published_at) {
                    // player tried to play before the set was created.
                    // large chance of ignoring this user makes older sets have more players
                    if (rand(0, 3) !== 0) {
                        continue;
                    }
                    // player played game before achievements existed
                }

                $achievementsRemaining = $numAchievements;
                if ($user->points_hardcore + $user->points === 0) {
                    $hardcore = (rand(0, 1) === 1);
                } else {
                    $hardcore = ($user->points_hardcore > $user->points);
                }
                $keepPlayingChance = rand(75, 100);
                $num_sessions = 1;

                $playerSession = $resumePlayerSessionAction->execute($user, $game, timestamp: $date);
                $playerSession->created_at = $date;

                $playerGame = PlayerGame::where('user_id', $user->id)->where('game_id', $game->id)->firstOrFail();
                $playerGame->created_at = $date;

                $date = $date->addSeconds(rand(100, 2000));

                foreach ($game->achievements()->promoted()->get() as $achievement) {
                    if ($date < $achievement->created_at || $date < $set->achievements_first_published_at) {
                        // player playing before set was released. don't unlock any achievements
                        break;
                    }

                    if ($achievement->type !== AchievementType::Progression) {
                        if ($achievement->type === AchievementType::WinCondition) {
                            if (rand(1, $numWinConditions) !== 1) {
                                // win condition - 1/X chance of unlocking it
                                continue;
                            }
                        } elseif ($achievement->type === AchievementType::Missable) {
                            if (rand(1, 3) === 1) {
                                // missable, 33% chance to not unlock it
                                continue;
                            }
                        } elseif (rand(1, 8) === 1) {
                            // non-progression, 12% chance to not unlock it
                            continue;
                        }
                    }

                    $unlock = $user->playerAchievements()->firstOrCreate([
                        'achievement_id' => $achievement->id,
                        'unlocked_at' => $date,
                        'unlocked_hardcore_at' => $hardcore ? $date : null,
                    ]);

                    // time advances
                    $date = $date->addSeconds(rand(0, rand(10, 500) + rand(10, 500) + rand(10, 500) + rand(10, 500)));

                    if (rand(0, $achievementsRemaining) === 0) {
                        // player gives up
                        break;
                    }

                    if ($hardcore && rand(1, 40) === 1) {
                        // player switches to softcore
                        $hardcore = false;
                    }

                    if (rand(0, $keepPlayingChance) === 0) {
                        // player gave up
                        break;
                    }
                    $keepPlayingChance = max(0, $keepPlayingChance - rand(0, (int) floor($achievementsRemaining / 3)));

                    if (rand(0, (int) floor($numAchievements / $num_sessions)) > $achievementsRemaining) {
                        // player takes a break

                        $playerSession->rich_presence = ucfirst($faker->words(rand(2, 10), true));
                        $playerSession->rich_presence_updated_at = $date;
                        $playerSession->duration = (int) $date->diffInMinutes($playerSession->created_at, true);
                        $playerSession->save();

                        $date = $date->addMinutes((int) sqrt(rand(500 * 500, 100000 * 100000))); // 8 hours to three month break, weighted toward shorter period
                        if ($date < Carbon::now()) {
                            $playerSession = $resumePlayerSessionAction->execute($user, $game, timestamp: $date);
                            $playerSession->created_at = $date;
                            $date = $date->addSeconds(rand(100, 2000));
                        }
                    }

                    if ($date > Carbon::now()) {
                        break;
                    }
                }

                // finalize the session
                $playerSession->rich_presence = ucfirst($faker->words(rand(2, 10), true));
                $playerSession->rich_presence_updated_at = $date;
                $playerSession->duration = (int) $date->diffInMinutes($playerSession->created_at, true);
                $playerSession->save();

                // aggregate metrics for player
                $updatePlayerGameMetricsAction->execute($playerGame, silent: true, seeding: true);

                // update points
                $playerGame->refresh();
                $user->points_hardcore += $playerGame->points_hardcore;
                $user->points += $playerGame->points;
                $user->saveQuietly();
            }

            // update player count and unlock metrics
            (new UpdateGameMetricsAction())->execute($game);
            (new UpdateGamePlayerCountAction())->execute($game);
            (new UpdateGameBeatenMetricsAction())->execute($game);
            (new UpdateGameAchievementsMetricsAction())->execute($game);
        });

        // small number of games without achievements should have players
        Game::where('achievements_published', 0)->inRandomOrder()->limit(15)->each(function (Game $game) use ($maxPlayers, $faker) {
            $numPlayers = (int) sqrt(rand(1, $maxPlayers));
            foreach (User::inRandomOrder()->limit($numPlayers)->get() as $user) {
                $date = Carbon::parse($faker->dateTimeBetween($user->created_at, '-2 hours')->format(DateTime::ATOM));

                $playerSession = new ResumePlayerSessionAction()->execute($user, $game, timestamp: $date);
                $playerSession->created_at = $date;

                $playerGame = PlayerGame::where('user_id', $user->id)->where('game_id', $game->id)->firstOrFail();
                $playerGame->created_at = $date;

                $date = $date->addMinutes(rand(1, 15) + rand(0, 10) + rand(0, 10) + rand(0, 10))->addSeconds(rand(0, 120));

                // finalize the session
                $playerSession->rich_presence = 'Playing ' . $game->title;
                $playerSession->rich_presence_updated_at = $date;
                $playerSession->duration = (int) $date->diffInMinutes($playerSession->created_at, true);
                $playerSession->save();

                // aggregate metrics for player
                new UpdatePlayerGameMetricsAction()->execute($playerGame, silent: true, seeding: true);
            }

            // update player count metrics
            (new UpdateGameMetricsAction())->execute($game);
            (new UpdateGamePlayerCountAction())->execute($game);
        });

        // update player metrics
        $updatePlayerMetricsAction = new UpdatePlayerMetricsAction();
        foreach (User::where('points_hardcore', '>', 0)->orWhere('points', '>', 0)->get() as $player) {
            $updatePlayerMetricsAction->execute($player);
        }

        // update developer contribution yields
        $developers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR, Role::DEVELOPER_RETIRED]);
        })->get();
        $updateDeveloperContributionYieldAction = new UpdateDeveloperContributionYieldAction();
        foreach ($developers as $developer) {
            $updateDeveloperContributionYieldAction->execute($developer);
        }
    }
}
