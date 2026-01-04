<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\Role;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Actions\RevalidateAchievementSetBadgeEligibilityAction;
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
use Illuminate\Support\Facades\Queue;

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
                $userTotalPoints = $user->points_hardcore + $user->points;
                if ($userTotalPoints > 0 && rand(0, $userTotalPoints) < 10) {
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
                if ($userTotalPoints === 0) {
                    // slightly skew towards hardcore as that's the default and users can switch to softcore
                    $hardcore = (rand(0, 2) !== 0);
                } else {
                    $hardcore = ($user->points_hardcore > $user->points);
                }
                $keepPlayingChance = rand(75, 100);
                $num_sessions = 1;

                $playerSession = null;
                Queue::fakeFor(function () use (&$playerSession, $resumePlayerSessionAction, $user, $game, $date) {
                    $playerSession = $resumePlayerSessionAction->execute($user, $game, timestamp: $date);
                });
                $playerSession->created_at = $date;

                $playerGame = PlayerGame::where('user_id', $user->id)->where('game_id', $game->id)->firstOrFail();
                $playerGame->created_at = $date;

                $date = $date->addSeconds(rand(100, 2000));
                $checkForBeat = false;

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
                            $checkForBeat = true;
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

                    if (rand(0, $achievementsRemaining + 2) === 0) {
                        // player gives up
                        break;
                    }

                    if ($hardcore) {
                        // if they have less than 100 points, there's a 1% chance of switching to softcore.
                        // the more hardcore points a player has, the less likely they are to switch to softcore.
                        // also, skew the chance by the number of achievements remaining - the player is more likely
                        // to switch to softcore for the last couple of achievements than the first ones.
                        $switchToSoftcoreChance = (int) (max(100, $user->points_hardcore) * 0.75 * sqrt($achievementsRemaining));

                        if ($user->points > 0) {
                            // if user already has some softcore points, they're more likely to switch
                            $switchToSoftcoreChance = (int) ($switchToSoftcoreChance / 2);
                        }

                        if (rand(1, $switchToSoftcoreChance) === 1) {
                            $hardcore = false;
                        }
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
                            Queue::fakeFor(function () use (&$playerSession, $resumePlayerSessionAction, $user, $game, $date) {
                                $playerSession = $resumePlayerSessionAction->execute($user, $game, timestamp: $date);
                            });
                            $playerSession->created_at = $date;
                            $date = $date->addSeconds(rand(100, 2000));
                        }
                    }

                    if ($date > Carbon::now()) {
                        break;
                    }

                    $achievementsRemaining--;
                }

                // finalize the session
                $playerSession->rich_presence = ucfirst($faker->words(rand(2, 10), true));
                $playerSession->rich_presence_updated_at = $date;
                $playerSession->duration = (int) $date->diffInMinutes($playerSession->created_at, true);
                $playerSession->save();

                // aggregate metrics for player
                // use a fake queue to prevent updating game metrics until we're done seeding the game
                Queue::fakeFor(fn () => $updatePlayerGameMetricsAction->execute($playerGame, silent: true));

                // if at least one win condition achievement was earned, or all achievements were earned, check for beat/mastery
                if ($checkForBeat || $achievementsRemaining === 0) {
                    new RevalidateAchievementSetBadgeEligibilityAction()->execute($playerGame);
                }

                // update points
                $playerGame->refresh();
                $user->points_hardcore += $playerGame->points_hardcore;
                $user->points += ($playerGame->points - $playerGame->points_hardcore); // playerGame->points includes hardcore and softcore
                $user->saveQuietly();
            }

            // update player count and unlock metrics
            (new UpdateGameMetricsAction())->execute($game);
            (new UpdateGamePlayerCountAction())->execute($game);
            (new UpdateGameBeatenMetricsAction())->execute($game);
            (new UpdateGameAchievementsMetricsAction())->execute($game);

            expireGameTopAchievers($game->id);
        });

        // small number of games without achievements should have players
        Game::where('achievements_published', 0)->inRandomOrder()->limit(15)->each(function (Game $game) use ($maxPlayers, $faker) {
            $numPlayers = (int) sqrt(rand(1, $maxPlayers));
            foreach (User::inRandomOrder()->limit($numPlayers)->get() as $user) {
                $date = Carbon::parse($faker->dateTimeBetween($user->created_at, '-2 hours')->format(DateTime::ATOM));

                $playerSession = null;
                Queue::fakeFor(function () use (&$playerSession, $user, $game, $date) {
                    $playerSession = new ResumePlayerSessionAction()->execute($user, $game, timestamp: $date);
                });
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
                Queue::fakeFor(fn () => new UpdatePlayerGameMetricsAction()->execute($playerGame, silent: true));
            }

            // update player count metrics
            (new UpdateGameMetricsAction())->execute($game);
            (new UpdateGamePlayerCountAction())->execute($game);
        });

        // update most recent rich presence and last activity for each user
        User::all()->each(function (User $user) {
            $lastSession = PlayerSession::where('user_id', $user->id)->orderByDesc('rich_presence_updated_at')->first();
            if ($lastSession) {
                $user->rich_presence = $lastSession->rich_presence;
                $user->rich_presence_game_id = $lastSession->game_id;
                $user->rich_presence_updated_at = $lastSession->rich_presence_updated_at;

                switch (rand(0, 3)) {
                    case 0:
                        $user->last_activity_at = $lastSession->rich_presence_updated_at->addMinutes(rand(0, 60));
                        break;
                    case 1:
                        $user->last_activity_at = $lastSession->rich_presence_updated_at->addMinutes(rand(60, 500));
                        break;
                    case 2:
                        $user->last_activity_at = $lastSession->rich_presence_updated_at->addMinutes(rand(500, 2000));
                        break;
                    case 3:
                        $user->last_activity_at = $lastSession->rich_presence_updated_at->addMinutes(rand(2000, 10000));
                        break;
                }

                $user->last_activity_at = $user->last_activity_at->addSeconds(rand(0, 60));
                if ($user->last_activity_at > Carbon::now()) {
                    $user->last_activity_at = Carbon::now();
                }

                $user->saveQuietly();
            }
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
