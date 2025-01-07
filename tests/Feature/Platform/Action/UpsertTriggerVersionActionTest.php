<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Actions\UpsertTriggerVersionAction;
use App\Platform\Enums\TriggerableType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpsertTriggerVersionActionTest extends TestCase
{
    use RefreshDatabase;

    private UpsertTriggerVersionAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new UpsertTriggerVersionAction();
    }

    public function testItIgnoresModelsWithoutHasVersionedTriggerContract(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $trigger = $this->action->execute($user, 'test conditions');

        // Assert
        $this->assertNull($trigger);
    }

    public function testItCanCreateNewVersionedTriggersCorrectly(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create();
        $user = User::factory()->create();

        // Act
        $trigger = $this->action->execute($achievement, '0xHaaaa=0', versioned: true, user: $user);

        // Assert
        $this->assertNotNull($trigger);
        $this->assertEquals('0xHaaaa=0', $trigger->conditions);
        $this->assertEquals(1, $trigger->version);
        $this->assertEquals($user->id, $trigger->user_id);
        $this->assertNull($trigger->parent_id);

        $achievement->refresh();
        $this->assertEquals($achievement->trigger->conditions, $trigger->conditions);
    }

    public function testItUpdatesExistingUnversionedTriggers(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create();
        $user = User::factory()->create();

        $existingTrigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xHaaaa=0',
            'version' => null, // !!
            'user_id' => User::factory()->create()->id,
        ]);

        // Act
        $trigger = $this->action->execute($achievement, '0xHbbbb=0', versioned: false, user: $user);

        // Assert
        $this->assertNotNull($trigger);
        $this->assertEquals($existingTrigger->id, $trigger->id);
        $this->assertEquals('0xHbbbb=0', $trigger->conditions);
        $this->assertNull($trigger->version);
        $this->assertEquals($user->id, $trigger->user_id);

        $achievement->refresh();
        $this->assertEquals($achievement->triggers()->unversioned()->first()->conditions, $trigger->conditions);
    }

    public function testItCreatesNewTriggerVersionsWhenConditionsForVersionedTriggersChange(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create();
        $user = User::factory()->create();

        $existingTrigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xHaaaa=0',
            'version' => 1, // !!
            'user_id' => User::factory()->create()->id,
        ]);

        // Act
        $trigger = $this->action->execute($achievement, '0xHbbbb=0', versioned: true, user: $user);

        // Assert
        $this->assertNotNull($trigger);
        $this->assertNotEquals($existingTrigger->id, $trigger->id);
        $this->assertEquals('0xHbbbb=0', $trigger->conditions);
        $this->assertEquals(2, $trigger->version); // !!
        $this->assertEquals($existingTrigger->id, $trigger->parent_id);
        $this->assertEquals($user->id, $trigger->user_id);

        $achievement->refresh();
        $this->assertEquals($achievement->trigger->conditions, $trigger->conditions);
    }

    public function testItReturnsExistingTriggerWhenConditionsUnchanged(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create();
        $user = User::factory()->create();

        $existingTrigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xHaaaa=0', // !!
            'version' => 1,
            'user_id' => User::factory()->create()->id,
        ]);

        // Act
        $trigger = $this->action->execute(
            $achievement,
            '0xHaaaa=0', // !!
            versioned: true,
            user: $user
        );

        // Assert
        $this->assertNotNull($trigger);
        $this->assertEquals($existingTrigger->id, $trigger->id);
        $this->assertEquals('0xHaaaa=0', $trigger->conditions);
        $this->assertEquals(1, $trigger->version);
    }

    public function testItConvertsUnversionedTriggersToVersionedInPlace(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create();
        $user = User::factory()->create();

        $existingTrigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xHaaaa=0',
            'version' => null, // !!
            'user_id' => User::factory()->create()->id,
        ]);

        // Act
        $trigger = $this->action->execute(
            $achievement,
            '0xHaaaa=0', // !! we're not changing the condition
            versioned: true, // !! just setting the thing to versioned (publishing the achievement)
            user: $user
        );

        // Assert
        $this->assertNotNull($trigger);
        $this->assertEquals($existingTrigger->id, $trigger->id); // !! the id didn't change
        $this->assertEquals('0xHaaaa=0', $trigger->conditions);
        $this->assertEquals(1, $trigger->version); // !! versioned now
        $this->assertEquals($user->id, $trigger->user_id);
    }

    public function testItUpdatesTheDenormalizedTriggerIdOnModels(): void
    {
        // Arrange
        $achievement = Achievement::factory()->create(['trigger_id' => null]);
        $leaderboard = Leaderboard::factory()->create(['trigger_id' => null]);
        $game = Game::factory()->create(['trigger_id' => null]);

        // Act
        $achievementTrigger = $this->action->execute($achievement, '0xH1234=1');
        $leaderboardTrigger = $this->action->execute($leaderboard, '0xH5678=1');
        $gameTrigger = $this->action->execute($game, '0xH1234=1');

        // Assert
        $achievement->refresh();
        $leaderboard->refresh();
        $game->refresh();

        $this->assertEquals($achievementTrigger->id, $achievement->trigger_id);
        $this->assertEquals($leaderboardTrigger->id, $leaderboard->trigger_id);
        $this->assertEquals($gameTrigger->id, $game->trigger_id);
    }
}
