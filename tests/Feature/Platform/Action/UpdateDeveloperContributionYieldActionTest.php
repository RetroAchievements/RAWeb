<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\User;
use App\Platform\Actions\UpdateDeveloperContributionYieldAction;
use App\Platform\Enums\AchievementFlag;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdateDeveloperContributionYieldActionTest extends TestCase
{
    use RefreshDatabase;

    use TestsPlayerBadges;

    // don't want to use TestsPlayerAchievements because it causes the UpdateDeveloperContributionYield
    // event to be risen, which makes it hard to do the testing in a controlled manner.

    protected function addHardcoreUnlock(User $user, Achievement $achievement, ?Carbon $when = null): void
    {
        if ($when === null) {
            $when = Carbon::now();
        }

        $user->playerAchievements()->Create([
            'achievement_id' => $achievement->id,
            'unlocked_at' => $when,
            'unlocked_hardcore_at' => $when,
        ]);
    }

    protected function addSoftcoreUnlock(User $user, Achievement $achievement, ?Carbon $when = null): void
    {
        if ($when === null) {
            $when = Carbon::now();
        }

        $user->playerAchievements()->Create([
            'achievement_id' => $achievement->id,
            'unlocked_at' => $when,
        ]);
    }

    protected function assertPointBadgeTier(User $user, int $expectedTier, ?int $displayOrder = null): void
    {
        $badge = $user->playerBadges()->where('AwardType', AwardType::AchievementPointsYield)->orderBy('AwardData', 'DESC')->first();
        if ($expectedTier === 0) {
            $this->assertNull($badge);
        } else {
            $this->assertGreaterThanOrEqual($badge?->AwardData, $expectedTier);
        }

        if ($displayOrder !== null) {
            $this->assertEquals($displayOrder, $badge?->DisplayOrder);
        }
    }

    protected function removeUnlock(User $user, Achievement $achievement): void
    {
        $user->playerAchievements()->where('achievement_id', $achievement->ID)->delete();
    }

    public function testBadgeUpgrades(): void
    {
        /** @var User $author */
        $author = User::factory()->create();
        /** @var User $player1 */
        $player1 = User::factory()->create();
        /** @var User $player2 */
        $player2 = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        //         0    1     2     3     4
        $points = [1, 999, 1500, 2500, 2500];
        $achievements = [];
        foreach ($points as $pointValue) {
            $achievements[] = Achievement::factory()->published()->create([
                'GameID' => $game->id,
                'Points' => $pointValue,
                'user_id' => $author->id,
            ]);
        }

        // no unlocks yet
        $action = new UpdateDeveloperContributionYieldAction();
        $action->execute($author);
        $this->assertEquals($author->ContribCount, 0);
        $this->assertEquals($author->ContribYield, 0);
        $this->assertPointBadgeTier($author, 0);

        // not enough points to cross tier
        $this->addHardcoreUnlock($player1, $achievements[1]);
        $action->execute($author);
        $this->assertEquals(1, $author->ContribCount);
        $this->assertEquals(999, $author->ContribYield);
        $this->assertPointBadgeTier($author, 0);

        // ignore unlocks from author
        $this->addHardcoreUnlock($author, $achievements[2]);
        $action->execute($author);
        $this->assertEquals(1, $author->ContribCount);
        $this->assertEquals(999, $author->ContribYield);
        $this->assertPointBadgeTier($author, 0);

        // single point unlock to cross first tier
        $this->addHardcoreUnlock($player2, $achievements[0]);
        $action->execute($author);
        $this->assertEquals(2, $author->ContribCount);
        $this->assertEquals(1000, $author->ContribYield);
        $this->assertPointBadgeTier($author, 1, 1);

        // reset unlock does remove contributions, but does not remove award
        $this->removeUnlock($player2, $achievements[0]);
        $action->execute($author);
        $this->assertEquals(1, $author->ContribCount);
        $this->assertEquals(999, $author->ContribYield);
        $this->assertPointBadgeTier($author, 1);

        // new unlock does not reach next tier (softcode vs. hardcore doesn't matter)
        $this->addSoftcoreUnlock($player2, $achievements[2]);
        $action->execute($author);
        $this->assertEquals(2, $author->ContribCount);
        $this->assertEquals(2499, $author->ContribYield);
        $this->assertPointBadgeTier($author, 1);

        // new unlock does reach next tier
        $this->addHardcoreUnlock($player2, $achievements[3]);
        $action->execute($author);
        $this->assertEquals(3, $author->ContribCount);
        $this->assertEquals(4999, $author->ContribYield);
        $this->assertPointBadgeTier($author, 2, 1);

        // demoted achievement removes contributions, but not badge.
        $achievements[3]->Flags = AchievementFlag::Unofficial;
        $achievements[3]->save();
        $action->execute($author);
        $this->assertEquals(2, $author->ContribCount);
        $this->assertEquals(2499, $author->ContribYield);
        $this->assertPointBadgeTier($author, 2);

        // new unlock does not reach next tier
        $this->addHardcoreUnlock($player2, $achievements[4]);
        $action->execute($author);
        $this->assertEquals(3, $author->ContribCount);
        $this->assertEquals(4999, $author->ContribYield);
        $this->assertPointBadgeTier($author, 2);

        // promoted achievement restores contributions, crosses tier, and awards new badge.
        $achievements[3]->Flags = AchievementFlag::OfficialCore;
        $achievements[3]->save();
        $action->execute($author);
        $this->assertEquals(4, $author->ContribCount);
        $this->assertEquals(7499, $author->ContribYield);
        $this->assertPointBadgeTier($author, 3, 1);
    }

    public function testAddMissing(): void
    {
        /** @var User $author */
        $author = User::factory()->create();
        /** @var User $player */
        $player = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        //            0     1     2     3
        $points = [1000, 1500, 2500, 5000];
        $achievements = [];
        foreach ($points as $pointValue) {
            $achievements[] = Achievement::factory()->published()->create([
                'GameID' => $game->id,
                'Points' => $pointValue,
                'user_id' => $author->id,
            ]);
        }

        // no unlocks yet
        $action = new UpdateDeveloperContributionYieldAction();
        $action->execute($author);
        $this->assertEquals($author->ContribCount, 0);
        $this->assertEquals($author->ContribYield, 0);
        $this->assertPointBadgeTier($author, 0);

        $now = Carbon::parse('2020-05-05');
        Carbon::setTestNow($now);

        // enough points for first tier
        $date1 = Carbon::parse('2020-01-01');
        $this->addHardcoreUnlock($player, $achievements[0], $date1);
        $action->execute($author);
        $this->assertEquals(1, $author->ContribCount);
        $this->assertEquals(1000, $author->ContribYield);
        $this->assertPointBadgeTier($author, 1, 1);

        // enough points for fourth tier
        $date2 = Carbon::parse('2020-02-02');
        $this->addHardcoreUnlock($player, $achievements[1], $date2);
        $date3 = Carbon::parse('2020-03-03');
        $this->addHardcoreUnlock($player, $achievements[2], $date3);
        $date4 = Carbon::parse('2020-04-04');
        $this->addHardcoreUnlock($player, $achievements[3], $date4);
        $action->execute($author);
        $this->assertEquals(4, $author->ContribCount);
        $this->assertEquals(10000, $author->ContribYield);
        $this->assertPointBadgeTier($author, 3, 1);

        $badges = $author->playerBadges()->where('AwardType', AwardType::AchievementPointsYield)->orderBy('AwardData', 'DESC')->get();
        $this->assertEquals($now, $badges->get(0)->AwardDate); // non-backfilled always set to now
        $this->assertEquals($date3, $badges->get(1)->AwardDate); // backfilled should have extrapolated date
        $this->assertEquals($date2, $badges->get(2)->AwardDate); // backfilled should have extrapolated date
        $this->assertEquals($now, $badges->get(3)->AwardDate); // non-backfilled, despite being valid historically
    }
}
