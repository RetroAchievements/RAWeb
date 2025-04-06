<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Actions\UpdateDeveloperContributionYieldAction;
use App\Platform\Actions\UpdateGameMetricsAction;
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
            $numWinConditions = $game->achievements()->published()->where('type', AchievementType::WinCondition)->count();
            $numAchievements = $game->achievements()->published()->count();

            $updatePlayerGameMetricsAction = new UpdatePlayerGameMetricsAction();
            $resumePlayerSessionAction = new ResumePlayerSessionAction();

            $numPlayers = (int) sqrt(random_int(1, $maxPlayers * $maxPlayers));
            foreach (User::inRandomOrder()->limit($numPlayers)->get() as $user) {
                $achievementsRemaining = $numAchievements;
                if ($user->RAPoints + $user->RASoftcorePoints === 0) {
                    $hardcore = (random_int(0, 1) === 1);
                } else {
                    $hardcore = ($user->RAPoints > $user->RASoftcorePoints);
                }
                $keepPlayingChance = random_int(75, 100);
                $num_sessions = 1;

                $date = Carbon::parse($faker->dateTimeBetween('-3 years', '-2 hours')->format(DateTime::ATOM));
                $playerSession = $resumePlayerSessionAction->execute($user, $game, timestamp: $date);
                $playerSession->created_at = $date;

                $playerGame = PlayerGame::where('user_id', $user->id)->where('game_id', $game->id)->firstOrFail();
                $playerGame->created_at = $date;

                $date = $date->addSeconds(random_int(100, 2000));

                foreach ($game->achievements()->published()->get() as $achievement) {
                    if ($achievement->type !== AchievementType::Progression) {
                        if ($achievement->type === AchievementType::WinCondition) {
                            if (random_int(1, $numWinConditions) !== 1) {
                                // win condition - 1/X chance of unlocking it
                                continue;
                            }
                        } elseif ($achievement->type === AchievementType::Missable) {
                            if (random_int(1, 3) === 1) {
                                // missable, 33% chance to not unlock it
                                continue;
                            }
                        } elseif (random_int(1, 8) === 1) {
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
                    $date = $date->addSeconds(random_int(0, random_int(10, 500) + random_int(10, 500) + random_int(10, 500) + random_int(10, 500)));

                    if (random_int(0, $achievementsRemaining) === 0) {
                        // player gives up
                        break;
                    }

                    if ($hardcore && random_int(1, 40) === 1) {
                        // player switches to softcore
                        $hardcore = false;
                    }

                    if (random_int(0, $keepPlayingChance) === 0) {
                        // player gave up
                        break;
                    }
                    $keepPlayingChance = max(0, $keepPlayingChance - random_int(0, (int) floor($achievementsRemaining / 3)));

                    if (random_int(0, (int) floor($numAchievements / $num_sessions)) > $achievementsRemaining) {
                        // player takes a break

                        $playerSession->rich_presence = ucfirst($faker->words(random_int(2, 10), true));
                        $playerSession->rich_presence_updated_at = $date;
                        $playerSession->duration = $date->diffInMinutes($playerSession->created_at);
                        $playerSession->save();

                        $date = $date->addMinutes((int) sqrt(random_int(500 * 500, 100000 * 100000))); // 8 hours to three month break, weighted toward shorter period
                        if ($date < Carbon::now()) {
                            $playerSession = $resumePlayerSessionAction->execute($user, $game, timestamp: $date);
                            $playerSession->created_at = $date;
                            $date = $date->addSeconds(random_int(100, 2000));
                        }
                    }

                    if ($date > Carbon::now()) {
                        break;
                    }
                }

                // finalize the session
                $playerSession->rich_presence = ucfirst($faker->words(random_int(2, 10), true));
                $playerSession->rich_presence_updated_at = $date;
                $playerSession->duration = $date->diffInMinutes($playerSession->created_at);
                $playerSession->save();

                // aggregate metrics for player
                $updatePlayerGameMetricsAction->execute($playerGame, silent: true);

                // update points
                $playerGame->refresh();
                $user->RAPoints += $playerGame->points_hardcore;
                $user->RASoftcorePoints += $playerGame->points;
                $user->save();
            }

            // update player count and unlock metrics
            (new UpdateGameMetricsAction())->execute($game);
        });

        // update player metrics
        $updatePlayerMetricsAction = new UpdatePlayerMetricsAction();
        foreach (User::where('RAPoints', '>', 0)->orWhere('RASoftcorePoints', '>', 0)->get() as $player) {
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
