<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAward;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\StaticData;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class RevalidateAchievementSetBadgeEligibilityActionTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    private function getPlayerBadge(User $user, Event $event): ?PlayerBadge
    {
        return $user->playerBadges->where('award_type', AwardType::Event)
            ->where('award_key', $event->id)
            ->first();
    }

    public function testBadgeUpgrade(): void
    {
        $user = User::factory()->create();
        $system = System::factory()->create(['id' => System::Events]);
        $game = $this->seedGame(system: $system);
        $achievements = $this->seedAchievements(8, $game);
        foreach ($achievements as $achievement) {
            $achievement->points = 1;
            $achievement->save();
        }
        $game->points_total = 8;
        $game->save();
        $event = Event::create(['legacy_game_id' => $game->id]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 1, 'points_required' => 2]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 2, 'points_required' => 4]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 3, 'points_required' => 6]);

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
        $this->assertEquals(1, $badge->award_tier);
        $this->assertEquals($now, $badge->awarded_at);

        $later = $now->clone()->addMinutes(10);
        Carbon::setTestNow($later);

        // badge not upgraded after three unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(2)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(1, $badge->award_tier);
        $this->assertEquals($now, $badge->awarded_at);

        // badge upgraded after four unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(3)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(2, $badge->award_tier);
        $this->assertEquals(1, $user->playerBadges()->count()); // badge should be upgraded, not new badge awarded
        $this->assertEquals($later, $badge->awarded_at);

        $tomorrow = $now->clone()->addHours(30);
        Carbon::setTestNow($tomorrow);

        // badge not upgraded after five unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(4)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(2, $badge->award_tier);
        $this->assertEquals($later, $badge->awarded_at);

        // badge upgraded after six unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(5)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(3, $badge->award_tier);
        $this->assertEquals(1, $user->playerBadges()->count()); // badge should be upgraded, not new badge awarded
        $this->assertEquals($tomorrow, $badge->awarded_at);

        // badge not upgraded after seven unlocks
        $this->addHardcoreUnlock($user, $achievements->skip(6)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(3, $badge->award_tier);
        $this->assertEquals($tomorrow, $badge->awarded_at);

        // badge not upgraded after eight unlocks (no 'mastery' on this event)
        $this->addHardcoreUnlock($user, $achievements->skip(7)->first());
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(3, $badge->award_tier);
        $this->assertEquals($tomorrow, $badge->awarded_at);
    }

    public function testBadgeUpgradeWeighted(): void
    {
        $user = User::factory()->create();
        System::factory()->create(['id' => System::Events]);
        $game = Game::factory()->create(['system_id' => System::Events, 'achievements_published' => 3, 'points_total' => 6]);
        $achievement1 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 1]);
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 1]);
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 2]);
        $achievement4 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 2]);
        $event = Event::create(['legacy_game_id' => $game->id]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 1, 'points_required' => 2]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 2, 'points_required' => 4]);
        EventAward::factory()->create(['event_id' => $event->id, 'tier_index' => 3, 'points_required' => 6]);

        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        // no badge to start
        $this->assertNull($this->getPlayerBadge($user, $event));

        // unlocking 2 pointer should award 2 point badge
        $this->addHardcoreUnlock($user, $achievement3);
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(1, $badge->award_tier);
        $this->assertEquals($now, $badge->awarded_at);

        $later = $now->clone()->addMinutes(10);
        Carbon::setTestNow($later);

        // additional 1 pointer is not enough for the 4 point badge
        $this->addHardcoreUnlock($user, $achievement1);
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(1, $badge->award_tier);
        $this->assertEquals($now, $badge->awarded_at);

        // additional 2 pointer bumps the user to 5 points, which is enough for the 4 point badge
        $this->addHardcoreUnlock($user, $achievement4);
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(2, $badge->award_tier);
        $this->assertEquals(1, $user->playerBadges()->count()); // badge should be upgraded, not new badge awarded
        $this->assertEquals($later, $badge->awarded_at);

        $tomorrow = $now->clone()->addHours(30);
        Carbon::setTestNow($tomorrow);

        // additional 1 pointer bumps the user to 6 points, which is enough for the final badge
        $this->addHardcoreUnlock($user, $achievement2);
        $badge = $this->getPlayerBadge($user, $event);
        $this->assertNotNull($badge);
        $this->assertEquals(3, $badge->award_tier);
        $this->assertEquals($tomorrow, $badge->awarded_at);
    }

    public function testNonTieredEvent(): void
    {
        $user = User::factory()->create();
        $eventSystem = System::factory()->create(['id' => System::Events]);
        $game = $this->seedGame(system: $eventSystem);
        $achievements = $this->seedAchievements(3, $game);
        $event = Event::create(['legacy_game_id' => $game->id]);

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
        $this->assertEquals(0, $badge->award_tier);
    }

    public function testUpdatesStaticData(): void
    {
        $user = User::factory()->create();
        $game = $this->seedGame();
        $game->points_total = 10;
        $game->save();

        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'points' => 1]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 1]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 1]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->promoted()->progression()->create(['game_id' => $game->id, 'points' => 1]);
        /** @var Achievement $achievement5 */
        $achievement5 = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'points' => 1]);
        /** @var Achievement $achievement6 */
        $achievement6 = Achievement::factory()->promoted()->winCondition()->create(['game_id' => $game->id, 'points' => 5]);

        $staticData = StaticData::factory()->create([
            'num_hardcore_mastery_awards' => 6,
            'num_hardcore_game_beaten_awards' => 8,
            'last_game_hardcore_mastered_game_id' => 11,
            'last_game_hardcore_beaten_game_id' => 11,
            'last_game_hardcore_mastered_user_id' => 9,
            'last_game_hardcore_beaten_user_id' => 9,
        ]);
        $originalMasteredAt = $staticData->last_game_hardcore_mastered_at;

        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        // no badge to start
        $this->assertDoesNotHaveBeatenBadge($user, $game);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        // unlock all progression and all but one non-progression achievements (no badge)
        $this->addHardcoreUnlock($user, $achievement1);
        $this->addHardcoreUnlock($user, $achievement2);
        $this->addHardcoreUnlock($user, $achievement3);
        $this->addHardcoreUnlock($user, $achievement4);

        // unlock win condition (expect beat badge)
        $beatenAt = $now->clone()->addMinutes(10);
        Carbon::setTestNow($beatenAt);

        $this->addHardcoreUnlock($user, $achievement6);
        $this->assertHasBeatenBadge($user, $game, 1);
        $this->assertDoesNotHaveMasteryBadge($user, $game);

        $staticData->refresh();
        $this->assertEquals(9, $staticData->num_hardcore_game_beaten_awards);
        $this->assertEquals($game->id, $staticData->last_game_hardcore_beaten_game_id);
        $this->assertEquals($user->id, $staticData->last_game_hardcore_beaten_user_id);
        $this->assertEquals($beatenAt, $staticData->last_game_hardcore_beaten_at);
        $this->assertEquals(6, $staticData->num_hardcore_mastery_awards);
        $this->assertEquals(11, $staticData->last_game_hardcore_mastered_game_id);
        $this->assertEquals(9, $staticData->last_game_hardcore_mastered_user_id);
        $this->assertEquals($originalMasteredAt, $staticData->last_game_hardcore_mastered_at);

        // unlock final achievement (expect master badge)
        $masteredAt = $beatenAt->clone()->addMinutes(5);
        Carbon::setTestNow($masteredAt);

        $this->addHardcoreUnlock($user, $achievement5);
        $this->assertHasBeatenBadge($user, $game, 1);
        $this->assertHasMasteryBadge($user, $game);

        $staticData->refresh();
        $this->assertEquals(9, $staticData->num_hardcore_game_beaten_awards);
        $this->assertEquals($game->id, $staticData->last_game_hardcore_beaten_game_id);
        $this->assertEquals($user->id, $staticData->last_game_hardcore_beaten_user_id);
        $this->assertEquals($beatenAt, $staticData->last_game_hardcore_beaten_at);
        $this->assertEquals(7, $staticData->num_hardcore_mastery_awards);
        $this->assertEquals($game->id, $staticData->last_game_hardcore_mastered_game_id);
        $this->assertEquals($user->id, $staticData->last_game_hardcore_mastered_user_id);
        $this->assertEquals($masteredAt, $staticData->last_game_hardcore_mastered_at);

        // ===== unranked user =====
        $unrankedUser = User::factory()->create(['unranked_at' => Carbon::now()]);

        $this->assertDoesNotHaveBeatenBadge($unrankedUser, $game);
        $this->assertDoesNotHaveMasteryBadge($unrankedUser, $game);

        // unlock all progression and all but one non-progression achievements (no badge)
        $this->addHardcoreUnlock($unrankedUser, $achievement1);
        $this->addHardcoreUnlock($unrankedUser, $achievement2);
        $this->addHardcoreUnlock($unrankedUser, $achievement3);
        $this->addHardcoreUnlock($unrankedUser, $achievement4);

        // unlock win condition (expect beat badge, but not static data changes)
        Carbon::setTestNow($now->clone()->addMinutes(30));

        $this->addHardcoreUnlock($unrankedUser, $achievement6);
        $this->assertHasBeatenBadge($unrankedUser, $game, 1);
        $this->assertDoesNotHaveMasteryBadge($unrankedUser, $game);

        $staticData->refresh();
        $this->assertEquals(9, $staticData->num_hardcore_game_beaten_awards);
        $this->assertEquals($game->id, $staticData->last_game_hardcore_beaten_game_id);
        $this->assertEquals($user->id, $staticData->last_game_hardcore_beaten_user_id);
        $this->assertEquals($beatenAt, $staticData->last_game_hardcore_beaten_at);

        // unlock final achievement (expect master badge but not static data changes)
        Carbon::setTestNow($now->clone()->addMinutes(35));

        $this->addHardcoreUnlock($unrankedUser, $achievement5);
        $this->assertHasBeatenBadge($unrankedUser, $game, 1);
        $this->assertHasMasteryBadge($unrankedUser, $game);

        $staticData->refresh();
        $this->assertEquals(7, $staticData->num_hardcore_mastery_awards);
        $this->assertEquals($game->id, $staticData->last_game_hardcore_mastered_game_id);
        $this->assertEquals($user->id, $staticData->last_game_hardcore_mastered_user_id);
        $this->assertEquals($masteredAt, $staticData->last_game_hardcore_mastered_at);
    }
}
