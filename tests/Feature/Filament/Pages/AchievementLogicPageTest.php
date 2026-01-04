<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Pages;

use App\Filament\Resources\AchievementResource\Pages\Logic as LogicPage;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Enums\TriggerableType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementLogicPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate a large conditions string with the specified number of conditions.
     */
    private function generateLargeConditions(int $numConditions): string
    {
        $conditions = [];
        for ($i = 0; $i < $numConditions; $i++) {
            $addr = sprintf('0x%06x', $i);
            $conditions[] = "0xH{$addr}=0x" . sprintf('%08x', $i);
        }

        return implode('_', $conditions);
    }

    public function testGetVersionHistoryDataReturnsLazyLoadFalseForEmptyTriggers(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $page = new LogicPage();
        $page->record = $achievement;

        // Act
        $result = $page->getVersionHistoryData();

        // Assert
        $this->assertFalse($result['lazyLoad']);
        $this->assertTrue($result['triggers']->isEmpty());
        $this->assertEmpty($result['summaries']);
        $this->assertEmpty($result['diffs']);
    }

    public function testGetVersionHistoryDataReturnsPreComputedDataBelowThreshold(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        // ... create triggers with small conditions (well below 50KB threshold) ...
        // ... each condition is roughly 30-50 chars, so 10 conditions ≈ 500 bytes ...
        $smallConditions = '0xH001234=1_0xH001235=2_0xH001236=3';

        Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'conditions' => $smallConditions,
            'version' => 1,
            'user_id' => $user->id,
        ]);
        Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'conditions' => $smallConditions . '_0xH001237=4',
            'version' => 2,
            'user_id' => $user->id,
        ]);

        $page = new LogicPage();
        $page->record = $achievement;

        // Act
        $result = $page->getVersionHistoryData();

        // Assert
        $this->assertFalse($result['lazyLoad']);
        $this->assertCount(2, $result['triggers']);
        $this->assertArrayHasKey('summaries', $result);
        $this->assertArrayHasKey('diffs', $result);
        $this->assertNotEmpty($result['summaries']);
        $this->assertNotEmpty($result['diffs']);

        $this->assertArrayHasKey(1, $result['summaries']);
        $this->assertArrayHasKey(2, $result['summaries']);
        $this->assertEquals('Initial version', $result['summaries'][1]);

        $this->assertArrayHasKey(1, $result['diffs']);
        $this->assertArrayHasKey(2, $result['diffs']);
    }

    public function testGetVersionHistoryDataReturnsLazyLoadTrueAboveThreshold(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        // ... create a trigger with conditions exceeding the 50KB threshold ...
        $largeConditions = $this->generateLargeConditions(3000);

        Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'conditions' => $largeConditions,
            'version' => 1,
            'user_id' => $user->id,
        ]);

        $page = new LogicPage();
        $page->record = $achievement;

        // Act
        $result = $page->getVersionHistoryData();

        // Assert
        $this->assertTrue($result['lazyLoad']);
        $this->assertCount(1, $result['triggers']);
        $this->assertArrayNotHasKey('summaries', $result);
        $this->assertArrayNotHasKey('diffs', $result);
    }

    public function testLoadAllSummariesReturnsFormattedSummaries(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $conditions1 = '0xH001234=1';
        $conditions2 = '0xH001234=1_0xH001235=2';

        Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'conditions' => $conditions1,
            'version' => 1,
            'user_id' => $user->id,
        ]);
        Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'conditions' => $conditions2,
            'version' => 2,
            'user_id' => $user->id,
        ]);

        $page = new LogicPage();
        $page->record = $achievement;

        // Act
        $result = $page->loadAllSummaries();

        // Assert
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertEquals('Initial version', $result[1]);
        $this->assertStringContainsString('added', $result[2]);
    }

    public function testLoadVersionDiffReturnsDiffForSpecificVersion(): void
    {
        // Arrange
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create(['game_id' => $game->id]);

        $conditions1 = '0xH001234=1';
        $conditions2 = '0xH001234=1_0xH001235=2';

        Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'conditions' => $conditions1,
            'version' => 1,
            'user_id' => $user->id,
        ]);
        Trigger::factory()->create([
            'triggerable_type' => TriggerableType::Achievement,
            'triggerable_id' => $achievement->id,
            'conditions' => $conditions2,
            'version' => 2,
            'user_id' => $user->id,
        ]);

        $page = new LogicPage();
        $page->record = $achievement;

        // Act
        $result = $page->loadVersionDiff(2);

        // Assert
        $this->assertArrayHasKey('diff', $result);
        $this->assertNotEmpty($result['diff']);
        $this->assertArrayHasKey('Label', $result['diff'][0]);
        $this->assertArrayHasKey('Conditions', $result['diff'][0]);
    }
}
