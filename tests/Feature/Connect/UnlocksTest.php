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
use App\Platform\Services\VirtualGameIdService;
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
        $user = User::factory()->create(['connect_token' => Str::random(16)]);
        $this->user = $user;
    }

    public function testUnlocks(): void
    {
        $game = $this->seedGame(withHash: false);
        /** @var Achievement $achievement1 */
        $achievement1 = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        /** @var Achievement $achievement4 */
        $achievement4 = Achievement::factory()->promoted()->create(['game_id' => $game->id]);

        $this->upsertGameCoreSetAction->execute($game);

        /** @var Game $bonusGame */
        $bonusGame = Game::factory()->create([
            'system_id' => $game->system_id,
            'title' => $game->title . ' [Subset - Bonus]',
        ]);
        /** @var Achievement $bonusAchievement1 */
        $bonusAchievement1 = Achievement::factory()->promoted()->create(['game_id' => $bonusGame->id]);
        /** @var Achievement $bonusAchievement2 */
        $bonusAchievement2 = Achievement::factory()->promoted()->create(['game_id' => $bonusGame->id]);

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
            ->get($this->apiUrl('unlocks', ['g' => $game->id, 'h' => 0]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => false,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $achievement3->id,
                    $bonusAchievement1->id,
                    $bonusAchievement2->id,
                ],
            ]);

        // hardcore unlocks for the game
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->id, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $bonusAchievement1->id,
                ],
            ]);

        // hardcore filter not specified, return all unlocks for the game
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->id]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => false,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $achievement3->id,
                    $bonusAchievement1->id,
                    $bonusAchievement2->id,
                ],
            ]);

        // all unlocks for the game (outdated client)
        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('unlocks', ['g' => $game->id, 'h' => 0]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => false,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $achievement3->id,
                    $bonusAchievement1->id,
                    $bonusAchievement2->id,
                    Achievement::CLIENT_WARNING_ID,
                ],
            ]);

        // hardcore unlocks for the game (outdated client)
        $this->withHeaders(['User-Agent' => $this->userAgentOutdated])
            ->get($this->apiUrl('unlocks', ['g' => $game->id, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $bonusAchievement1->id,
                ],
            ]);

        // hardcore unlocks for the game (unsupported client)
        $this->withHeaders(['User-Agent' => $this->userAgentUnsupported])
            ->get($this->apiUrl('unlocks', ['g' => $game->id, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $bonusAchievement1->id,
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
            ->post('dorequest.php', $this->apiParams('unlocks', ['g' => $game->id, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $bonusAchievement1->id,
                ],
            ]);

        // not-unlocked event achievement hides hardcore unlock when active
        System::factory()->create(['id' => System::Events]);
        /** @var Game $eventGame */
        $eventGame = Game::factory()->create(['system_id' => System::Events]);
        /** @var Achievement $eventAchievement1 */
        $eventAchievement1 = Achievement::factory()->promoted()->create(['game_id' => $eventGame->id]);

        $this->upsertGameCoreSetAction->execute($eventGame);

        Carbon::setTestNow($now->addWeeks(1));
        EventAchievement::create([
            'achievement_id' => $eventAchievement1->id,
            'source_achievement_id' => $achievement1->id,
            'active_from' => $now->clone()->subDays(1),
            'active_until' => $now->clone()->addDays(2),
        ]);

        // softcore ignores event achievement
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->id, 'h' => 0]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => false,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $achievement3->id,
                    $bonusAchievement1->id,
                    $bonusAchievement2->id,
                ],
            ]);

        // hardcore ignores event achievement when untracked
        $this->user->unranked_at = Carbon::now();
        $this->user->save();
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->id, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $bonusAchievement1->id,
                ],
            ]);
        $this->user->unranked_at = null;
        $this->user->save();

        // hardcore excludes active event achievement
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->id, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement2->id,
                    $bonusAchievement1->id,
                ],
            ]);

        // event achievement returned as unlocked after unlocking it
        $this->addHardcoreUnlock($this->user, $eventAchievement1, $now);
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->id, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => true,
                'UserUnlocks' => [
                    $achievement1->id,
                    $achievement2->id,
                    $bonusAchievement1->id,
                ],
            ]);

        // empty response when passing incompatible game id
        $this->addHardcoreUnlock($this->user, $eventAchievement1, $now);
        $this->withHeaders(['User-Agent' => $this->userAgentValid])
            ->get($this->apiUrl('unlocks', ['g' => $game->id + VirtualGameIdService::IncompatibleIdBase, 'h' => 1]))
            ->assertExactJson([
                'Success' => true,
                'GameID' => $game->id,
                'HardcoreMode' => true,
                'UserUnlocks' => [],
            ]);
    }
}
