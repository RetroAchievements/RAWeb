<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerSession;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

/**
 * TODO migrate these test cases into PingTest after multiset is generally available.
 * These have to be separated because environment variables can only be changed at the
 * test suite level, not the test case level.
 */
class PingMultisetTest extends TestCase
{
    use BootstrapsConnect;
    use RefreshDatabase;
    use TestsPlayerAchievements;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('feature.enable_multiset', true);

        /** @var User $user */
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $this->user = $user;
    }

    public function testPingWithBonusSetResolvesToCoreGame(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::now());

        $system = System::factory()->create();
        $baseGame = Game::factory()->create(['ConsoleID' => $system->id]);
        $bonusGame = Game::factory()->create(['ConsoleID' => $system->id]);

        Achievement::factory()->published()->count(2)->create(['GameID' => $baseGame->id]);
        Achievement::factory()->published()->count(2)->create(['GameID' => $bonusGame->id]);

        $upsertGameCoreSetAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $associateAchievementSetToGameAction = new AssociateAchievementSetToGameAction();

        $upsertGameCoreSetAction->execute($baseGame);
        $upsertGameCoreSetAction->execute($bonusGame);
        $associateAchievementSetToGameAction->execute($baseGame, $bonusGame, AchievementSetType::Bonus, 'Bonus');

        $bonusGameHash = GameHash::factory()->create(['game_id' => $bonusGame->id]);

        $this->user->LastGameID = $bonusGame->id;
        $this->user->save();

        // Act
        $response = $this->post('dorequest.php', $this->apiParams('ping', [
            'g' => $bonusGame->id, // !!
            'm' => 'Playing bonus content',
            'x' => $bonusGameHash->md5,
        ]))
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        // Assert
        $response
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $playerSession = PlayerSession::latest()->first();

        $this->assertNotNull($playerSession);
        $this->assertEquals($this->user->id, $playerSession->user_id);
        $this->assertEquals($baseGame->id, $playerSession->game_id);
        $this->assertEquals($bonusGameHash->id, $playerSession->game_hash_id);
        $this->assertEquals('Playing bonus content', $playerSession->rich_presence);
        $this->assertEquals(1, $playerSession->duration);

        $this->assertEquals($baseGame->id, $this->user->fresh()->LastGameID);
        $this->assertEquals('Playing bonus content', $this->user->fresh()->RichPresenceMsg);
    }

    public function testPingWithSpecialtySetMaintainsSubsetGame(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::now());

        $system = System::factory()->create();
        $baseGame = Game::factory()->create(['ConsoleID' => $system->id]);
        $specialtyGame = Game::factory()->create(['ConsoleID' => $system->id]);

        Achievement::factory()->published()->count(2)->create(['GameID' => $baseGame->id]);
        Achievement::factory()->published()->count(2)->create(['GameID' => $specialtyGame->id]);

        $upsertGameCoreSetAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $associateAchievementSetToGameAction = new AssociateAchievementSetToGameAction();

        $upsertGameCoreSetAction->execute($baseGame);
        $upsertGameCoreSetAction->execute($specialtyGame);
        $associateAchievementSetToGameAction->execute($baseGame, $specialtyGame, AchievementSetType::Specialty, 'Specialty');

        $specialtyGameHash = GameHash::factory()->create(['game_id' => $specialtyGame->id]);

        $this->user->LastGameID = $specialtyGame->id;
        $this->user->save();

        // Act
        $response = $this->post('dorequest.php', $this->apiParams('ping', [
            'g' => $specialtyGame->id,
            'm' => 'Playing specialty content',
            'x' => $specialtyGameHash->md5,
        ]));

        // Assert
        $response
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $playerSession = PlayerSession::latest()->first();

        $this->assertNotNull($playerSession);
        $this->assertEquals($this->user->id, $playerSession->user_id);
        $this->assertEquals($specialtyGame->id, $playerSession->game_id); // !! should stay on specialty game
        $this->assertEquals($specialtyGameHash->id, $playerSession->game_hash_id);
        $this->assertEquals('Playing specialty content', $playerSession->rich_presence);
        $this->assertEquals(1, $playerSession->duration);

        $this->assertEquals($specialtyGame->id, $this->user->fresh()->LastGameID);
        $this->assertEquals('Playing specialty content', $this->user->fresh()->RichPresenceMsg);
    }

    public function testPingWithMultiDiscGameUsesGameIdDirectly(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::now());

        $system = System::factory()->create();
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $gameHash = GameHash::factory()->create([
            'game_id' => $game->id,
            'name' => 'Game Title (Disc 2)', // !! will be detected as multi-disc
        ]);

        $this->user->LastGameID = $game->id;
        $this->user->save();

        // Act
        $response = $this->post('dorequest.php', $this->apiParams('ping', [
            'g' => $game->id,
            'm' => 'Playing disc 2',
            'x' => $gameHash->md5,
        ]));

        // Assert
        $response
            ->assertStatus(200)
            ->assertExactJson(['Success' => true]);

        $playerSession = PlayerSession::latest()->first();

        $this->assertNotNull($playerSession);
        $this->assertEquals($this->user->id, $playerSession->user_id);
        $this->assertEquals($game->id, $playerSession->game_id);
        $this->assertNull($playerSession->game_hash_id);
        $this->assertEquals('Playing disc 2', $playerSession->rich_presence);

        $this->assertEquals($game->id, $this->user->fresh()->LastGameID);
        $this->assertEquals('Playing disc 2', $this->user->fresh()->RichPresenceMsg);
    }
}
