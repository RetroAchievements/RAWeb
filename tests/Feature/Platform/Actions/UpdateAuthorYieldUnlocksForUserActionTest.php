<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\AchievementMaintainerUnlock;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Actions\UpdateAuthorYieldUnlocksForUserAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class UpdateAuthorYieldUnlocksForUserActionTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerBadges;

    private UpdateAuthorYieldUnlocksForUserAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new UpdateAuthorYieldUnlocksForUserAction();
    }

    public function testItDecrementsAuthorYieldUnlocksWhenUserBecomesUnranked(): void
    {
        // Arrange
        $author = User::factory()->create();
        $player = User::factory()->create(['unranked_at' => null]);

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $author->id,
            'author_yield_unlocks' => 5,
        ]);

        // ... the player has unlocked this achievement ...
        PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // ... and then the player becomes unranked ...
        $player->update(['unranked_at' => Carbon::now()]);

        // Act
        $this->action->execute($player);
        $achievement->refresh();

        // Assert
        $this->assertEquals(4, $achievement->author_yield_unlocks); // !! 5 - 1
    }

    public function testItIncrementsAuthorYieldUnlocksWhenUserBecomesReranked(): void
    {
        // Arrange
        $author = User::factory()->create();
        $player = User::factory()->create(['unranked_at' => Carbon::now()->subDay()]);

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $author->id,
            'author_yield_unlocks' => 5,
        ]);

        // ... the player has unlocked this achievement (while unranked) ...
        PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // ... and then the player becomes reranked ...
        $player->update(['unranked_at' => null]);

        // Act
        $this->action->execute($player);
        $achievement->refresh();

        // Assert
        $this->assertEquals(6, $achievement->author_yield_unlocks); // !! 5 + 1
    }

    public function testItDoesNotAffectAchievementsAuthoredByTheUser(): void
    {
        // Arrange
        $authorPlayer = User::factory()->create();

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $authorPlayer->id,
            'author_yield_unlocks' => 5,
        ]);

        // ... the author unlocks their own achievement ...
        PlayerAchievement::create([
            'user_id' => $authorPlayer->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // ... the author becomes unranked ...
        $authorPlayer->update(['unranked_at' => Carbon::now()]);

        // Act
        $this->action->execute($authorPlayer);
        $achievement->refresh();

        // Assert
        $this->assertEquals(5, $achievement->author_yield_unlocks); // unchanged
    }

    public function testItDoesNotAffectMaintainerCreditedUnlocks(): void
    {
        // Arrange
        $author = User::factory()->create();
        $maintainer = User::factory()->create();
        $player = User::factory()->create(['unranked_at' => null]);

        $game = $this->seedGame(withHash: false);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 50,
            'user_id' => $author->id,
            'author_yield_unlocks' => 5,
        ]);

        // ... the player has unlocked this achievement ...
        $playerAchievement = PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // ... but the unlock was credited to the maintainer, not the author ...
        AchievementMaintainerUnlock::create([
            'player_achievement_id' => $playerAchievement->id,
            'maintainer_id' => $maintainer->id,
            'achievement_id' => $achievement->id,
        ]);

        // ... the player becomes unranked ...
        $player->update(['unranked_at' => Carbon::now()]);

        // Act
        $this->action->execute($player);
        $achievement->refresh();

        // Assert
        $this->assertEquals(5, $achievement->author_yield_unlocks); // unchanged
    }

    public function testItUpdatesMultipleAchievementsConcurrently(): void
    {
        // Arrange
        $author = User::factory()->create();
        $player = User::factory()->create(['unranked_at' => null]);

        $game = $this->seedGame(withHash: false);

        $achievement1 = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 10,
            'user_id' => $author->id,
            'author_yield_unlocks' => 10,
        ]);
        $achievement2 = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'points' => 25,
            'user_id' => $author->id,
            'author_yield_unlocks' => 20,
        ]);

        PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement1->id,
            'unlocked_at' => Carbon::now(),
        ]);
        PlayerAchievement::create([
            'user_id' => $player->id,
            'achievement_id' => $achievement2->id,
            'unlocked_at' => Carbon::now(),
        ]);

        // ... the player becomes unranked ...
        $player->update(['unranked_at' => Carbon::now()]);

        // Act
        $this->action->execute($player);
        $achievement1->refresh();
        $achievement2->refresh();

        // Assert
        $this->assertEquals(9, $achievement1->author_yield_unlocks); // !! 10 - 1
        $this->assertEquals(19, $achievement2->author_yield_unlocks); // !! 20 - 1
    }
}
