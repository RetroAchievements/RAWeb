<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Listeners;

use App\Models\Achievement;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Enums\TriggerableType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class EnsureTriggerVersionedOnPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function testItVersionsUnversionedTriggerOnPromotion(): void
    {
        // Arrange
        $this->actingAs(User::factory()->create());

        $achievement = Achievement::factory()->create(['is_promoted' => false]);
        $trigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xHaaaa=0',
            'version' => null,
        ]);
        $achievement->update(['trigger_id' => $trigger->id]);

        // Act
        $achievement->update(['is_promoted' => true]);

        // Assert
        $trigger->refresh();
        $this->assertEquals(1, $trigger->version);
    }

    public function testItDoesNotChangeAlreadyVersionedTrigger(): void
    {
        // Arrange
        $this->actingAs(User::factory()->create());

        $achievement = Achievement::factory()->create(['is_promoted' => false]);
        $trigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xHaaaa=0',
            'version' => 3,
        ]);
        $achievement->update(['trigger_id' => $trigger->id]);

        // Act
        $achievement->update(['is_promoted' => true]);

        // Assert
        $trigger->refresh();
        $this->assertEquals(3, $trigger->version);
    }

    public function testItHandlesAchievementWithNoTrigger(): void
    {
        // Arrange
        $this->actingAs(User::factory()->create());

        $achievement = Achievement::factory()->create([
            'is_promoted' => false,
            'trigger_id' => null,
        ]);

        // Act
        $achievement->update(['is_promoted' => true]);

        // Assert
        // ... we're just checking that this doesn't throw ...
        $this->assertTrue($achievement->is_promoted);
    }

    public function testItThrowsWhenNoAuthenticatedUser(): void
    {
        $achievement = Achievement::factory()->create(['is_promoted' => false]);
        $trigger = Trigger::factory()->create([
            'triggerable_id' => $achievement->id,
            'triggerable_type' => TriggerableType::Achievement,
            'conditions' => '0xHaaaa=0',
            'version' => null,
        ]);
        $achievement->update(['trigger_id' => $trigger->id]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot version trigger: no authenticated user.');

        $achievement->update(['is_promoted' => true]);
    }
}
