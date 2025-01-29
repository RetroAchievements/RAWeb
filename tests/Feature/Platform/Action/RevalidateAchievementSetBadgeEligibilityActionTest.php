<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAward;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class RevalidateAchievementSetBadgeEligibilityActionTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    private function getPlayerBadge(User $user, Event $event): ?PlayerBadge
    {
        return $user->playerBadges->where('AwardType', AwardType::Event)
            ->where('AwardData', $event->id)
            ->first();
    }

    public function testBadgeUpgrade(): void
    {
        $user = User::factory()->create();
        System::factory()->create(['ID' => System::Events]);
        $game = Game::factory()->create(['ConsoleID' => System::Events, 'achievements_published' => 8, 'points_total' => 8]);
        $achievements = $game->achievements()->saveMany(Achievement::factory()->published()
            ->count(8)->create(['Points' => 1]));
        $event = Event::create(['legacy_game_id' => $game->id, 'slug' => 'test-event']);
        EventAward::create(['event_id' => $event->id, 'tier_index' => 1, 'label' => 'Bronze', 'points_required' => 2, 'image_asset_path' => '/Images/000001.png']);
        EventAward::create(['event_id' => $event->id, 'tier_index' => 2, 'label' => 'Silver', 'points_required' => 4, 'image_asset_path' => '/Images/000002.png']);
        EventAward::create(['event_id' => $event->id, 'tier_index' => 3, 'label' => 'Gold', 'points_required' => 6, 'image_asset_path' => '/Images/000003.png']);

        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        // no badge to start
        $this->assertNull($this->getPlayerBadge($user, $event));

        // no badge after one unlock
        $this->addHardcoreUnlock($user, $achievements->first());
        $this->assertNull($this->getPlayerBadge($user, $event));

        // badge after two unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(1)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(1, $badge->AwardDataExtra);
        $this->assertEquals($now, $badge->AwardDate);

        $later = $now->clone()->addMinutes(10);
        Carbon::setTestNow($later);

        // badge not upgraded after three unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(2)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(1, $badge->AwardDataExtra);
        $this->assertEquals($now, $badge->AwardDate);

        // badge upgraded after four unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(3)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(2, $badge->AwardDataExtra);
        $this->assertEquals(1, $user->playerBadges()->count()); // badge should be upgraded, not new badge awarded
        $this->assertEquals($later, $badge->AwardDate);

        $tomorrow = $now->clone()->addHours(30);
        Carbon::setTestNow($tomorrow);

        // badge not upgraded after five unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(4)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(2, $badge->AwardDataExtra);
        $this->assertEquals($later, $badge->AwardDate);

        // badge upgraded after six unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(5)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(3, $badge->AwardDataExtra);
        $this->assertEquals(1, $user->playerBadges()->count()); // badge should be upgraded, not new badge awarded
        $this->assertEquals($tomorrow, $badge->AwardDate);

        // badge not upgraded after seven unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(6)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(3, $badge->AwardDataExtra);
        $this->assertEquals($tomorrow, $badge->AwardDate);

        // badge not upgraded after eight unlocks (no 'mastery' on this event)
        $this->addHardcoreUnlock($user, $achievements->skip(7)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(3, $badge->AwardDataExtra);
        $this->assertEquals($tomorrow, $badge->AwardDate);
    }

    public function testNonTieredEvent(): void
    {
        $user = User::factory()->create();
        System::factory()->create(['ID' => System::Events]);
        $game = Game::factory()->create(['ConsoleID' => System::Events]);
        $achievements = $this->seedAchievements(3, $game);
        $event = Event::create(['legacy_game_id' => $game->id, 'slug' => 'test-event']);

        // no badge to start
        $this->assertNull($this->getPlayerBadge($user, $event));

        // no badge after one unlock
        $this->addHardcoreUnlock($user, $achievements->first());
        $this->assertNull($this->getPlayerBadge($user, $event));

        // no badge after two unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(1)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNull($this->getPlayerBadge($user, $event));

        // badge awarded after three unlocks (tier 0 = event icon)
        $this->addHardcoreUnlock($user, $achievements->skip(2)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(0, $badge->AwardDataExtra);
    }
}
