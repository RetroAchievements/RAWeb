<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Platform\Actions\UpdateTotalGamesCountAction;
use App\Platform\Enums\PlayerStatRankingKind;
use App\Platform\Enums\UnlockMode;
use App\Platform\Jobs\UpdateBeatenGamesLeaderboardJob;
use App\Platform\Jobs\UpdateGameAchievementsMetricsJob;
use App\Platform\Jobs\UpdateGamePlayerCountJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertGameToEvent extends Command
{
    protected $signature = "ra:platform:game:convert-to-event
                            {gameId : Unique ID of the game to convert}";
    protected $description = "Converts a game to an event";

    public function handle(): void
    {
        $gameId = $this->argument('gameId');
        $game = Game::find($gameId);
        if (!$game) {
            $this->error("Unknown game");

            return;
        }

        if ($game->achievementSets->count() > 1) {
            $this->error("Cannot convert game with subsets");

            return;
        }

        DB::transaction(function () use ($game) {
            // create an event wrapper for the game
            $event = Event::create([
                'legacy_game_id' => $game->id,
                'image_asset_path' => $game->image_icon_asset_path,
            ]);

            // wrap the achievements in EventAchievements
            foreach ($game->achievements()->promoted()->get() as $achievement) {
                EventAchievement::create([
                    'achievement_id' => $achievement->id,
                ]);
            }

            // delete any subset associations
            $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();
            GameAchievementSet::where('achievement_set_id', $gameAchievementSet->achievement_set_id)
                ->where('id', '!=', $gameAchievementSet->id)
                ->delete();

            // delete associated hashes so player can't load the achievements any more
            GameHash::where('game_id', $game->id)->delete();

            // move the game to the events console
            $systemId = $game->system_id;
            $game->system_id = System::Events;
            $game->save();

            // delete beat badges
            PlayerBadge::where('award_type', AwardType::GameBeaten)
                ->where('award_key', $game->id)
                ->delete();

            // delete softcore unlocks
            PlayerAchievement::query()
                ->whereNull('unlocked_hardcore_at')
                ->whereIn('achievement_id', $game->achievements->pluck('id'))
                ->delete();

            // delete completed (softcore mastery) badges
            PlayerBadge::where('award_type', AwardType::Mastery)
                ->where('award_key', $game->id)
                ->where('award_tier', UnlockMode::Softcore)
                ->delete();

            // move mastery badges
            PlayerBadge::where('award_type', AwardType::Mastery)
                ->where('award_key', $game->id)
                ->update([
                    'award_type' => AwardType::Event,
                    'award_key' => $event->id,
                    'award_tier' => 0,
                ]);

            // update metrics
            UpdateGameAchievementsMetricsJob::dispatch($game->id);
            UpdateGamePlayerCountJob::dispatch($game->id);
            app()->make(UpdateTotalGamesCountAction::class)->execute();

            foreach (PlayerStatRankingKind::beatenCases() as $kind) {
                UpdateBeatenGamesLeaderboardJob::dispatch($systemId, $kind)->onQueue('game-beaten-metrics');
            }

            // done
            $this->info('Created event ' . $event->id);
        });
    }
}
