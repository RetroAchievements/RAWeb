<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\CreateAchievementOfTheWeek;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class CreateAchievementOfTheWeekTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testCreateEmpty(): void
    {
        /** @var System $eventSystem */
        $eventSystem = System::factory()->create(['ID' => System::Events]);

        $event = (new CreateAchievementOfTheWeek())->execute(Carbon::parse('2023-01-02'));

        $this->assertEquals('Achievement of the Week 2023', $event->title);
        $this->assertEquals(System::Events, $event->system->id);
        $this->assertEquals(64, $event->achievements()->count());

        $achievement = $event->achievements()->first();
        $this->assertEquals('Week 1', $achievement->Title);
        $this->assertEquals('TBD', $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore->value, $achievement->Flags);
        $this->assertEquals(EventAchievement::RAEVENTS_USER_ID, $achievement->user_id);
        $this->assertEquals('00000', $achievement->BadgeName);
        $this->assertEquals(1, $achievement->DisplayOrder);
        $this->assertEquals(null, $achievement->eventData->sourceAchievement);
        $this->assertEquals(Carbon::parse('2023-01-02'), $achievement->eventData->active_from);
        $this->assertEquals(Carbon::parse('2023-01-09'), $achievement->eventData->active_until);

        $achievement = $event->achievements()->firstWhere('DisplayOrder', 52);
        $this->assertEquals('Week 52', $achievement->Title);
        $this->assertEquals('TBD', $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore->value, $achievement->Flags);
        $this->assertEquals(EventAchievement::RAEVENTS_USER_ID, $achievement->user_id);
        $this->assertEquals('00000', $achievement->BadgeName);
        $this->assertEquals(52, $achievement->DisplayOrder);
        $this->assertEquals(null, $achievement->eventData->sourceAchievement);
        $this->assertEquals(Carbon::parse('2023-12-25'), $achievement->eventData->active_from);
        $this->assertEquals(Carbon::parse('2024-01-01'), $achievement->eventData->active_until);

        $achievement = $event->achievements()->firstWhere('DisplayOrder', 53);
        $this->assertEquals('January Achievement of the Month', $achievement->Title);
        $this->assertEquals('TBD', $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore->value, $achievement->Flags);
        $this->assertEquals(EventAchievement::RAEVENTS_USER_ID, $achievement->user_id);
        $this->assertEquals('00000', $achievement->BadgeName);
        $this->assertEquals(53, $achievement->DisplayOrder);
        $this->assertEquals(null, $achievement->eventData->sourceAchievement);
        $this->assertEquals(Carbon::parse('2023-01-02'), $achievement->eventData->active_from);
        $this->assertEquals(Carbon::parse('2023-02-06'), $achievement->eventData->active_until);

        $achievement = $event->achievements()->firstWhere('DisplayOrder', 64);
        $this->assertEquals('December Achievement of the Month', $achievement->Title);
        $this->assertEquals('TBD', $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore->value, $achievement->Flags);
        $this->assertEquals(EventAchievement::RAEVENTS_USER_ID, $achievement->user_id);
        $this->assertEquals('00000', $achievement->BadgeName);
        $this->assertEquals(64, $achievement->DisplayOrder);
        $this->assertEquals(null, $achievement->eventData->sourceAchievement);
        $this->assertEquals(Carbon::parse('2023-12-04'), $achievement->eventData->active_from);
        $this->assertEquals(Carbon::parse('2024-01-01'), $achievement->eventData->active_until);

        // existing event should be returned if trying to recreate
        $event2 = (new CreateAchievementOfTheWeek())->execute(Carbon::parse('2023-01-02'));

        $this->assertEquals($event->id, $event2->id);
        $this->assertEquals(64, $event2->achievements()->count());
    }

    public function testCreateFromAchievementList(): void
    {
        /** @var User $player1 */
        $player1 = User::factory()->create();
        /** @var User $player2 */
        $player2 = User::factory()->create();
        /** @var User $player3 */
        $player3 = User::factory()->create();

        /** @var System $system */
        $system = System::factory()->create();
        /** @var System $eventSystem */
        $eventSystem = System::factory()->create(['ID' => System::Events]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        /** @var Achievement $sourceAchievement1 */
        $sourceAchievement1 = Achievement::factory()->published()->create(['GameID' => $game->id]);
        /** @var Achievement $sourceAchievement2 */
        $sourceAchievement2 = Achievement::factory()->published()->create(['GameID' => $game->id]);

        $time1 = Carbon::parse('2024-01-04 03:15:47');
        $time2 = Carbon::parse('2024-01-06 17:13:02');
        $time3 = Carbon::parse('2024-01-08 00:00:06');
        $time4 = Carbon::parse('2024-01-10 12:07:26');

        $this->addHardcoreUnlock($player1, $sourceAchievement1, $time1);
        $this->addHardcoreUnlock($player2, $sourceAchievement1, $time2);
        $this->addHardcoreUnlock($player3, $sourceAchievement1, $time3);
        $this->addHardcoreUnlock($player1, $sourceAchievement2, $time2);
        $this->addHardcoreUnlock($player2, $sourceAchievement2, $time3);
        $this->addHardcoreUnlock($player3, $sourceAchievement2, $time4);

        $lastLogin = Carbon::parse('2020-01-02 03:04:05');
        $player1->LastLogin = $lastLogin;
        $player1->save();

        $event = (new CreateAchievementOfTheWeek())->execute(Carbon::parse('2024-01-01'), [$sourceAchievement1->id, $sourceAchievement2->id]);

        $this->assertEquals('Achievement of the Week 2024', $event->title);
        $this->assertEquals(System::Events, $event->system->id);
        $this->assertEquals(64, $event->achievements()->count());

        $achievement = $event->achievements()->first();
        $this->assertEquals($sourceAchievement1->Title, $achievement->Title);
        $this->assertEquals($sourceAchievement1->Description, $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore->value, $achievement->Flags);
        $this->assertEquals(EventAchievement::RAEVENTS_USER_ID, $achievement->user_id);
        $this->assertEquals($sourceAchievement1->BadgeName, $achievement->BadgeName);
        $this->assertEquals(1, $achievement->DisplayOrder);
        $this->assertEquals($sourceAchievement1->id, $achievement->eventData->sourceAchievement->id);
        $this->assertEquals(Carbon::parse('2024-01-01'), $achievement->eventData->active_from);
        $this->assertEquals(Carbon::parse('2024-01-08'), $achievement->eventData->active_until);
        $this->assertEquals(2, $achievement->unlocks_hardcore_total);
        $this->assertEquals(2, $achievement->unlocks_total);

        $achievement = $event->achievements()->firstWhere('DisplayOrder', 2);
        $this->assertEquals($sourceAchievement2->Title, $achievement->Title);
        $this->assertEquals($sourceAchievement2->Description, $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore->value, $achievement->Flags);
        $this->assertEquals(EventAchievement::RAEVENTS_USER_ID, $achievement->user_id);
        $this->assertEquals($sourceAchievement2->BadgeName, $achievement->BadgeName);
        $this->assertEquals(2, $achievement->DisplayOrder);
        $this->assertEquals($sourceAchievement2->id, $achievement->eventData->sourceAchievement->id);
        $this->assertEquals(Carbon::parse('2024-01-08'), $achievement->eventData->active_from);
        $this->assertEquals(Carbon::parse('2024-01-15'), $achievement->eventData->active_until);
        $this->assertEquals(2, $achievement->unlocks_hardcore_total);
        $this->assertEquals(2, $achievement->unlocks_total);

        $achievement = $event->achievements()->firstWhere('DisplayOrder', 52);
        $this->assertEquals('Week 52', $achievement->Title);
        $this->assertEquals('TBD', $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore->value, $achievement->Flags);
        $this->assertEquals(EventAchievement::RAEVENTS_USER_ID, $achievement->user_id);
        $this->assertEquals('00000', $achievement->BadgeName);
        $this->assertEquals(52, $achievement->DisplayOrder);
        $this->assertEquals(null, $achievement->eventData->sourceAchievement);
        $this->assertEquals(Carbon::parse('2024-12-23'), $achievement->eventData->active_from);
        $this->assertEquals(Carbon::parse('2025-01-06'), $achievement->eventData->active_until);

        $achievement = $event->achievements()->firstWhere('DisplayOrder', 53);
        $this->assertEquals('January Achievement of the Month', $achievement->Title);
        $this->assertEquals('TBD', $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore->value, $achievement->Flags);
        $this->assertEquals(EventAchievement::RAEVENTS_USER_ID, $achievement->user_id);
        $this->assertEquals('00000', $achievement->BadgeName);
        $this->assertEquals(53, $achievement->DisplayOrder);
        $this->assertEquals(null, $achievement->eventData->sourceAchievement);
        $this->assertEquals(Carbon::parse('2024-01-01'), $achievement->eventData->active_from);
        $this->assertEquals(Carbon::parse('2024-02-05'), $achievement->eventData->active_until);

        $game->refresh();
        $this->assertEquals(3, $game->players_hardcore);
        $this->assertEquals(3, $game->players_total);

        // existing event should be returned if trying to recreate
        $event2 = (new CreateAchievementOfTheWeek())->execute(Carbon::parse('2024-01-01'), [$sourceAchievement1->id, $sourceAchievement2->id]);

        $this->assertEquals($event->id, $event2->id);
        $this->assertEquals(64, $event2->achievements()->count());

        // unlocking event achievements should not generate user activity
        $player1->refresh();
        $this->assertEquals($lastLogin, $player1->LastLogin);
    }
}
