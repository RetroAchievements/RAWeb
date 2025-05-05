<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\System;
use App\Platform\Actions\UpdatePlayerGameMetricsAction;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Services\PlayerGameActivityService;
use Illuminate\Console\Command;

class UpdatePlayerEstimatedTimes extends Command
{
    protected $signature = 'ra:platform:player:update-estimated-times';

    protected $description = 'Updates estimated play times for player_games';

    public function handle(): void
    {
        $playerGames = PlayerGame::whereNull('playtime_total');
        $count = $playerGames->count();

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $playerGames->with(['game.system', 'user'])
            ->chunkById(500, function ($playerGames) use ($progressBar) {
                /** @var PlayerGame $playerGame */
                foreach ($playerGames as $playerGame) {
                    $game = $playerGame->game;
                    if (System::isGameSystem($game->system->id)) {
                        if (mb_strpos($game->title, "[Subset")) {
                            // do a full execution against the parent game
                            $parentGame = getParentGameFromGameTitle($game->title, $game->system->id);
                            if ($parentGame !== null) {
                                $parentPlayerGame = PlayerGame::where('game_id', $parentGame->id)->where('user_id', $playerGame->user->id)->first();
                                if ($parentPlayerGame) {
                                    (new UpdatePlayerGameMetricsAction())->execute($parentPlayerGame, silent: true);
                                    continue;
                                }
                            }
                        }

                        // quick-and-dirty core set only
                        $coreAchievementSet = $game->achievementSets()->where('type', AchievementSetType::Core)->first();

                        $activityService = new PlayerGameActivityService();
                        $activityService->initialize($playerGame->user, $game);
                        $summary = $activityService->summarize($playerGame);
                        $beatSummary = $activityService->getBeatProgressMetrics($coreAchievementSet, $playerGame);

                        $playerGame->fill([
                            'playtime_total' => $summary['totalPlaytime'],
                            'all_achievements_total' => $game->achievements_published,
                            'all_achievements_unlocked' => $playerGame->achievements_unlocked,
                            'all_achievements_unlocked_hardcore' => $playerGame->achievements_unlocked_hardcore,
                            'all_points_total' => $game->points_total,
                            'all_points' => $playerGame->points,
                            'all_points_hardcore' => $playerGame->points_hardcore,
                            'all_points_weighted' => $playerGame->points_weighted,
                            'time_to_beat' => $beatSummary['beatPlaytimeSoftcore'],
                            'time_to_beat_hardcore' => $beatSummary['beatPlaytimeHardcore'],
                            'beaten_dates' => $playerGame->beaten_dates ? array_unique($playerGame->beaten_dates) : null,
                            'beaten_dates_hardcore' => $playerGame->beaten_dates_hardcore ? array_unique($playerGame->beaten_dates_hardcore) : null,
                            'completion_dates' => $playerGame->completion_dates ? array_unique($playerGame->completion_dates) : null,
                            'completion_dates_hardcore' => $playerGame->completion_dates_hardcore ? array_unique($playerGame->completion_dates_hardcore) : null,
                        ]);
                        $playerGame->save();

                        $coreSetSummary = $activityService->getAchievementSetMetrics($coreAchievementSet);
                        $playerAchievementSet = PlayerAchievementSet::updateOrCreate([
                            'user_id' => $playerGame->user->id,
                            'achievement_set_id' => $coreAchievementSet->id,
                        ], [
                            'achievements_unlocked' => $playerGame->achievements_unlocked,
                            'achievements_unlocked_hardcore' => $playerGame->achievements_unlocked_hardcore,
                            'achievements_unlocked_softcore' => $playerGame->achievements_unlocked_softcore,
                            'completion_percentage' => $playerGame->completion_percentage,
                            'completion_percentage_hardcore' => $playerGame->completion_percentage_hardcore,
                            'completed_at' => $playerGame->completed_at,
                            'completed_hardcore_at' => $playerGame->completed_hardcore_at,
                            'completion_dates' => $playerGame->completion_dates,
                            'completion_dates_hardcore' => $playerGame->completion_dates_hardcore,
                            'time_taken' => $coreSetSummary['achievementPlaytimeSoftcore'] ?? 0,
                            'time_taken_hardcore' => $coreSetSummary['achievementPlaytimeHardcore'] ?? 0,
                            'last_unlock_at' => $playerGame->last_unlock_at,
                            'last_unlock_hardcore_at' => $playerGame->last_unlock_hardcore_at,
                            'points' => $playerGame->points,
                            'points_hardcore' => $playerGame->points_hardcore,
                            'points_weighted' => $playerGame->points_weighted,
                            'created_at' => $playerGame->created_at,
                        ]);
                    }
                    $progressBar->advance();
                }
            });
    }
}
