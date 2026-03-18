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
        $aotwAchievements = Achievement::query()
            ->where('game_id', $aotw->legacy_game_id)
            ->with('eventData')
            ->orderBy('order_column')
            ->get();
        foreach ($aotwAchievements as $achievement) {
            if ($date < Carbon::now()) {
                $sourceAchievement = Achievement::promoted()->inRandomOrder()->first();
                $achievement->title = $sourceAchievement->title;
                $achievement->image_name = $sourceAchievement->image_name;
                $achievement->save();

                $achievement->eventData->source_achievement_id = $sourceAchievement->id;
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
