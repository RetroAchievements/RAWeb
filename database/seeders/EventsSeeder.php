<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\EventAward;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\ComputeSortTitleAction;
use App\Platform\Actions\UpdateGameAchievementsMetricsAction;
use App\Platform\Actions\UpdateGameBeatenMetricsAction;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Actions\UpdateGamePlayerCountAction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    public function run(): void
    {
        if (Event::count() > 0) {
            return;
        }

        $this->createAchievementOfTheWeek();
        $this->createDevJam();

        // update metrics
        Game::where('system_id', System::Events)->each(function (Game $game) {
            (new UpdateGameMetricsAction())->execute($game);
            (new UpdateGamePlayerCountAction())->execute($game);
            (new UpdateGameBeatenMetricsAction())->execute($game);
            (new UpdateGameAchievementsMetricsAction())->execute($game);
        });
    }

    private function createAchievementOfTheWeek(): void
    {
        $date = Carbon::now()->startOfYear();
        $aotw = $this->createEvent("Achievement of the Week {$date->year}", 52, 'Week');
        $aotw->active_from = $date->clone();

        $aotwPlayers = [];
        $aotwAchievements = Achievement::query()
            ->where('game_id', $aotw->legacy_game_id)
            ->with('eventData')
            ->orderBy('order_column')
            ->get();
        foreach ($aotwAchievements as $achievement) {
            if ($date < Carbon::now()) {
                // pick a random achievement with at least 8 unlocks so there's a decent chance the simulate play will unlock it.
                $sourceAchievement = Achievement::promoted()->where('unlocks_hardcore', '>', 8)->inRandomOrder()->first();
                $achievement->title = $sourceAchievement->title;
                $achievement->image_name = $sourceAchievement->image_name;
                $achievement->save();

                $achievement->eventData->source_achievement_id = $sourceAchievement->id;

                // between 15 and 40 players will join the first week. each subsequent week, an additional 6-12 will join.
                $first = empty($aotwPlayers);
                $newPlayers = $first ? rand(3, 8) + rand(3, 8) + rand(3, 8) + rand(3, 8) + rand(2, 6) : rand(6, 12);

                $users = User::query()
                    ->where('points_hardcore', '>', 'points')
                    ->where('created_at', '<', $date)
                    ->whereNull('unranked_at')
                    ->whereNotIn('id', array_column($aotwPlayers, 'id'))
                    ->inRandomOrder()
                    ->limit($newPlayers)
                    ->get();
                foreach ($users as $user) {
                    if (!in_array($user, $aotwPlayers)) {
                        $aotwPlayers[] = $user;
                    }
                }

                // randomize the player list - players at the end of the list are less likely to play this week
                shuffle($aotwPlayers);

                // drop a couple players out of the list who have given up on the event.
                if (!$first && count($aotwPlayers) > 20) {
                    array_pop($aotwPlayers);
                    array_pop($aotwPlayers);
                }

                // have some quantity of players of interested players attempt the AotW.
                $participantCount = count($aotwPlayers);
                if (!$first && $participantCount > 20) {
                    $participantCount = max(10, $participantCount - rand(1, 4) - rand(1, 4) - rand(1, 4) - rand(1, 4) - rand(1, 4));
                }
                foreach (array_slice($aotwPlayers, 0, $participantCount) as $user) {
                    $playerDate = $date->clone()->addMinutes(rand(5, 24 * 60 * 6));

                    $playerSession = PlayerSession::query()
                        ->where('user_id', $user->id)
                        ->where('game_id', $sourceAchievement->game_id)
                        ->where('created_at', '>', $playerDate);
                    if ($playerSession->exists()) {
                        // user already played this game after it was AotW - don't try to backfill AotW playthrough.
                        continue;
                    }

                    // attempt to play the game. the user may or may not reach the event achievement
                    PlayerAchievementsSeeder::simulatePlay($user, $sourceAchievement->game, $playerDate, true);
                }
            }

            $achievement->eventData->active_from = $date->clone();
            $date->addDays(7);
            $achievement->eventData->active_until = $date;
            $achievement->eventData->save();
        }
        $aotw->active_until = $date;
        $aotw->save();

        EventAward::create([
            'event_id' => $aotw->id,
            'tier_index' => 1,
            'label' => 'Bronze',
            'points_required' => 15,
            'image_asset_path' => $aotw->image_asset_path,
        ]);

        EventAward::create([
            'event_id' => $aotw->id,
            'tier_index' => 2,
            'label' => 'Silver',
            'points_required' => 30,
            'image_asset_path' => $aotw->image_asset_path,
        ]);

        EventAward::create([
            'event_id' => $aotw->id,
            'tier_index' => 3,
            'label' => 'Gold',
            'points_required' => 45,
            'image_asset_path' => $aotw->image_asset_path,
        ]);

        EventAward::create([
            'event_id' => $aotw->id,
            'tier_index' => 4,
            'label' => 'Platinum',
            'points_required' => 52,
            'image_asset_path' => $aotw->image_asset_path,
        ]);
    }

    private function createDevJam(): void
    {
        $devjam = $this->createEvent('DevJam', 6, isSiteEvent: true);
        $devjamAchievements = Achievement::query()
            ->where('game_id', $devjam->legacy_game_id)
            ->get();
        $index = 1;
        foreach ($devjamAchievements as $achievement) {
            $achievement->title = "DevJam $index";
            $achievement->description = "Participated in DevJam";
            $achievement->save();
            $index++;
        }

        $date = Carbon::now()->subDays(5);
        $developers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR, Role::DEVELOPER_RETIRED]);
        })->inRandomOrder()->limit(5)->get();
        foreach ($developers as $user) {
            PlayerGame::create([
                'user_id' => $user->id,
                'game_id' => $devjam->legacy_game_id,
            ]);

            foreach ($devjamAchievements as $achievement) {
                $unlock = $user->playerAchievements()->firstOrCreate([
                    'achievement_id' => $achievement->id,
                    'unlocked_at' => $date,
                    'unlocked_hardcore_at' => $date,
                ]);
            }
        }
    }

    private function createEvent(string $title, int $numAchievements = 6, ?string $prefix = null, bool $isSiteEvent = false): Event
    {
        $eventGame = new Game([
            'title' => $title,
            'sort_title' => (new ComputeSortTitleAction())->execute($title),
            'publisher' => 'RetroAchievements',
            'system_id' => System::Events,
        ]);
        // these properties are not fillable, so have to be set manually
        $eventGame->players_total = 0;
        $eventGame->players_hardcore = 0;
        $eventGame->points_total = 0;
        $eventGame->achievements_published = 0;
        $eventGame->achievements_unpublished = 0;
        $eventGame->save();

        $event = Event::create([
            'legacy_game_id' => $eventGame->id,
            'image_asset_path' => '/Images/000001.png',
        ]);

        $achievementCount = 0;
        for ($achievementCount = 0; $achievementCount < $numAchievements; $achievementCount++) {
            $achievement = Achievement::create([
                'title' => $prefix ? "$prefix $achievementCount" : "Placeholder",
                'description' => 'TBD',
                'trigger_definition' => '0=1',
                'points' => 1,
                'is_promoted' => true,
                'game_id' => $eventGame->id,
                'user_id' => $isSiteEvent ? EventAchievement::DEVQUEST_USER_ID : EventAchievement::RAEVENTS_USER_ID,
                'image_name' => '00000',
                'order_column' => $achievementCount,
            ]);

            EventAchievement::create([
                'achievement_id' => $achievement->id,
                'decorator' => $prefix ? $achievement->title : null,
            ]);
        }

        GameSetGame::create([
            'game_set_id' => $isSiteEvent ? GameSet::DeveloperEventsHubId : GameSet::CommunityEventsHubId,
            'game_id' => $eventGame->id,
        ]);

        return $event;
    }
}
