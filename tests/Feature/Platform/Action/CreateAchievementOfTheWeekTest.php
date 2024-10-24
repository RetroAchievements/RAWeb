<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Models\Achievement;
use App\Models\Comment;
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
        $this->assertEquals(52, $event->achievements()->count());

        $achievement = $event->achievements()->first();
        $this->assertEquals('Week 1', $achievement->Title);
        $this->assertEquals('TBD', $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore, $achievement->Flags);
        $this->assertEquals(Comment::SYSTEM_USER_ID, $achievement->user_id);
        $this->assertEquals('00000', $achievement->BadgeName);
        $this->assertEquals(1, $achievement->DisplayOrder);
        $this->assertEquals(null, $achievement->eventData()->sourceAchievement);
        $this->assertEquals(Carbon::parse('2023-01-02'), $achievement->eventData()->active_from);
        $this->assertEquals(Carbon::parse('2023-01-09'), $achievement->eventData()->active_until);

        $achievement = $event->achievements()->firstWhere('DisplayOrder', 52);
        $this->assertEquals('Week 52', $achievement->Title);
        $this->assertEquals('TBD', $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore, $achievement->Flags);
        $this->assertEquals(Comment::SYSTEM_USER_ID, $achievement->user_id);
        $this->assertEquals('00000', $achievement->BadgeName);
        $this->assertEquals(52, $achievement->DisplayOrder);
        $this->assertEquals(null, $achievement->eventData()->sourceAchievement);
        $this->assertEquals(Carbon::parse('2023-12-25'), $achievement->eventData()->active_from);
        $this->assertEquals(Carbon::parse('2024-01-01'), $achievement->eventData()->active_until);

        // existing event should be returned if trying to recreate
        $event2 = (new CreateAchievementOfTheWeek())->execute(Carbon::parse('2023-01-02'));

        $this->assertEquals($event->id, $event2->id);
        $this->assertEquals(52, $event2->achievements()->count());
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

        $event = (new CreateAchievementOfTheWeek())->execute(Carbon::parse('2024-01-01'), [$sourceAchievement1->id, $sourceAchievement2->id]);

        $this->assertEquals('Achievement of the Week 2024', $event->title);
        $this->assertEquals(System::Events, $event->system->id);
        $this->assertEquals(52, $event->achievements()->count());

        $achievement = $event->achievements()->first();
        $this->assertEquals($sourceAchievement1->Title, $achievement->Title);
        $this->assertEquals($sourceAchievement1->Description, $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore, $achievement->Flags);
        $this->assertEquals(Comment::SYSTEM_USER_ID, $achievement->user_id);
        $this->assertEquals($sourceAchievement1->BadgeName, $achievement->BadgeName);
        $this->assertEquals(1, $achievement->DisplayOrder);
        $this->assertEquals($sourceAchievement1->id, $achievement->eventData()->sourceAchievement->id);
        $this->assertEquals(Carbon::parse('2024-01-01'), $achievement->eventData()->active_from);
        $this->assertEquals(Carbon::parse('2024-01-08'), $achievement->eventData()->active_until);
        $this->assertEquals(2, $achievement->unlocks_hardcore_total);
        $this->assertEquals(2, $achievement->unlocks_total);

        $achievement = $event->achievements()->firstWhere('DisplayOrder', 2);
        $this->assertEquals($sourceAchievement2->Title, $achievement->Title);
        $this->assertEquals($sourceAchievement2->Description, $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore, $achievement->Flags);
        $this->assertEquals(Comment::SYSTEM_USER_ID, $achievement->user_id);
        $this->assertEquals($sourceAchievement2->BadgeName, $achievement->BadgeName);
        $this->assertEquals(2, $achievement->DisplayOrder);
        $this->assertEquals($sourceAchievement2->id, $achievement->eventData()->sourceAchievement->id);
        $this->assertEquals(Carbon::parse('2024-01-08'), $achievement->eventData()->active_from);
        $this->assertEquals(Carbon::parse('2024-01-15'), $achievement->eventData()->active_until);
        $this->assertEquals(2, $achievement->unlocks_hardcore_total);
        $this->assertEquals(2, $achievement->unlocks_total);

        $achievement = $event->achievements()->firstWhere('DisplayOrder', 52);
        $this->assertEquals('Week 52', $achievement->Title);
        $this->assertEquals('TBD', $achievement->Description);
        $this->assertEquals('0=1', $achievement->MemAddr);
        $this->assertEquals(AchievementFlag::OfficialCore, $achievement->Flags);
        $this->assertEquals(Comment::SYSTEM_USER_ID, $achievement->user_id);
        $this->assertEquals('00000', $achievement->BadgeName);
        $this->assertEquals(52, $achievement->DisplayOrder);
        $this->assertEquals(null, $achievement->eventData()->sourceAchievement);
        $this->assertEquals(Carbon::parse('2024-12-23'), $achievement->eventData()->active_from);
        $this->assertEquals(Carbon::parse('2025-01-01'), $achievement->eventData()->active_until);

        $game->refresh();
        $this->assertEquals(3, $game->players_hardcore);
        $this->assertEquals(3, $game->players_total);

        // existing event should be returned if trying to recreate
        $event2 = (new CreateAchievementOfTheWeek())->execute(Carbon::parse('2024-01-01'), [$sourceAchievement1->id, $sourceAchievement2->id]);

        $this->assertEquals($event->id, $event2->id);
        $this->assertEquals(52, $event2->achievements()->count());
    }
}
