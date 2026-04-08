<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Platform\Actions\UpdateTotalGamesCountAction;
use App\Platform\Enums\PlayerStatRankingKind;
use App\Platform\Jobs\UpdateBeatenGamesLeaderboardJob;
use App\Platform\Jobs\UpdateGamePlayerCountJob;
use Illuminate\Console\Command;

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

        // create an event wrapper for the game
        $event = Event::create([
            'legacy_game_id' => $gameId,
            'image_asset_path' => $game->image_icon_asset_path,
        ]);

        // wrap the achievements in EventAchievements
        foreach ($game->achievements()->promoted()->get() as $achievement) {
            EventAchievement::create([
                'achievement_id' => $achievement->id,
            ]);
        }

        // delete any subset associations
        $gameAchievementSet = GameAchievementSet::where('game_id', $gameId)->first();
        GameAchievementSet::where('achievement_set_id', $gameAchievementSet->achievement_set_id)
            ->where('id', '!=', $gameAchievementSet->id)
            ->delete();

        // move the game to the events console
        $systemId = $game->system_id;
        $game->system_id = System::Events;
        $game->save();

        // delete beat badges
        PlayerBadge::where('award_type', AwardType::GameBeaten)
            ->where('award_key', $game->id)
            ->delete();

        // move mastery badges
        PlayerBadge::where('award_type', AwardType::Mastery)
            ->where('award_key', $game->id)
            ->update([
                'award_type' => AwardType::Event,
                'award_key' => $event->id,
            ]);

        // update metrics
        UpdateGamePlayerCountJob::dispatch($game->id);
        app()->make(UpdateTotalGamesCountAction::class)->execute();

        foreach (PlayerStatRankingKind::beatenCases() as $kind) {
            UpdateBeatenGamesLeaderboardJob::dispatch($systemId, $kind)->onQueue('game-beaten-metrics');
        }

        // done
        $this->info('Created event ' . $event->id);
    }
}
