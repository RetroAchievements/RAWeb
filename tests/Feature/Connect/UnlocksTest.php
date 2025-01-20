<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\TestsEmulatorUserAgent;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class UnlocksTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsEmulatorUserAgent;
    use TestsPlayerAchievements;

    private UpsertGameCoreAchievementSetFromLegacyFlagsAction $upsertGameCoreSetAction;
    private AssociateAchievementSetToGameAction $associateAchievementSetToGameAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this->upsertGameCoreSetAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $this->associateAchievementSetToGameAction = new AssociateAchievementSetToGameAction();

        /** @var User $user */
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $this->user = $user;
    }

    public function testUnlocks(): void
    {
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->published()->create(['GameID' => $game->ID]);

        $this->upsertGameCoreSetAction->execute($game);

        /** @var Game $bonusGame */
        $bonusGame = Game::factory()->create([
            'ConsoleID' => $game->ConsoleID,
            'Title' => $game->title . ' [Subset - Bonus]',
        ]);
        /** @var Achievement $bonusAchievement1 */
        $bonusAchievement1 = Achievement::factory()->published()->create(['GameID' => $bonusGame->id]);
        /** @var Achievement $bonusAchievement2 */
        $bonusAchievement2 = Achievement::factory()->published()->create(['GameID' => $bonusGame->id]);

        $this->upsertGameCoreSetAction->execute($bonusGame);
        $this->associateAchievementSetToGameAction->execute($game, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $now = Carbon::now()->subSeconds(15); // 15-second offset so times aren't on the boundaries being queried

        $unlock1Date = $now->clone()->subMinutes(65);
        $this->addHardcoreUnlock($this->user, $achievement1, $unlock1Date);
        $unlock2Date = $now->clone()->subMinutes(22);
        $this->addHardcoreUnlock($this->user, $achievement2, $unlock2Date);
        $unlock3Date = $now->clone()->subMinutes(1);
        $this->addSoftcoreUnlock($this->user, $achievement3, $unlock3Date);

        $bonusUnlock1Date = $now->clone()->subMinutes(45);
        $this->addHardcoreUnlock($this->user, $bonusAchievement1, $bonusUnlock1Date);
        $bonusUnlock2Date = $now->clone()->subMinutes(15);
        $this->addSoftcoreUnlock($this->user, $bonusAchievement2, $bonusUnlock2Date);

        $this->seedEmulatorUserAgents();

        // all unlocks for the game
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 0]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => false,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $achievement3->ID,
                    $bonusAchievement1->ID,
                    $bonusAchievement2->ID,
                ],
            ]);

        // hardcore unlocks for the game
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $bonusAchievement1->ID,
                ],
            ]);

        // hardcore filter not specified, return all unlocks for the game
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => false,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $achievement3->ID,
                    $bonusAchievement1->ID,
                    $bonusAchievement2->ID,
                ],
            ]);

        // all unlocks for the game (outdated client)
        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 0]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => false,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $achievement3->ID,
                    $bonusAchievement1->ID,
                    $bonusAchievement2->ID,
                    Achievement::CLIENT_WARNING_ID,
                ],
            ]);

        // hardcore unlocks for the game (outdated client)
        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $bonusAchievement1->ID,
                ],
            ]);

        // hardcore unlocks for the game (unsupported client)
        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $bonusAchievement1->ID,
                ],
            ]);

        // unknown game ID
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => 9999]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => 9999,
                'HardcoreMode' => false,
                'UserUnlocks' => [],
            ]);

        // via POST
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->post('dorequest.php', $this->apiParams('unlocks', ['g' => $game->ID, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $bonusAchievement1->ID,
                ],
            ]);

        // not-unlocked event achievement hides hardcore unlock when active
        System::factory()->create(['ID' => System::Events]);
        /** @var Game $eventGame */
        $eventGame = Game::factory()->create(['ConsoleID' => System::Events]);
        /** @var Achievement $eventAchievement1 */
        $eventAchievement1 = Achievement::factory()->published()->create(['GameID' => $eventGame->ID]);

        $this->upsertGameCoreSetAction->execute($eventGame);

        Carbon::setTestNow($now->addWeeks(1));
        EventAchievement::create([
            'achievement_id' => $eventAchievement1->ID,
            'source_achievement_id' => $achievement1->ID,
            'active_from' => $now->clone()->subDays(1),
            'active_until' => $now->clone()->addDays(2),
        ]);

        // softcore ignores event achievement
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 0]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => false,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $achievement3->ID,
                    $bonusAchievement1->ID,
                    $bonusAchievement2->ID,
                ],
            ]);

        // hardcore ignores event achievement when untracked
        $this->user->unranked_at = Carbon::now();
        $this->user->save();
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $bonusAchievement1->ID,
                ],
            ]);
        $this->user->unranked_at = null;
        $this->user->save();

        // hardcore excludes active event achievement
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement2->ID,
                    $bonusAchievement1->ID,
                ],
            ]);

        // event achievement returned as unlocked after unlocking it
        $this->addHardcoreUnlock($this->user, $eventAchievement1, $now);
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->ID, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->ID,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->ID,
                    $achievement2->ID,
                    $bonusAchievement1->ID,
                ],
            ]);
    }
}
