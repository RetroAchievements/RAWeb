<?php

declare(strict_types=1);

namespace Tests\Feature\Connect\Actions;

use App\Connect\Actions\InjectPatchAotwEventDataAction;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class InjectPatchAotwEventDataActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any cached data to ensure tests start fresh.
        Cache::forget('aotw_achievement_data');
        Cache::forget('aotm_achievement_data');
    }

    private function createSamplePatchResponse(array $achievements = []): array
    {
        return [
            'Success' => true,
            'PatchData' => [
                'ID' => 123,
                'Title' => 'Test Game',
                'ImageIcon' => '/Images/000123.png',
                'RichPresencePatch' => 'Display:\nTest',
                'ConsoleID' => 1,
                'ImageIconURL' => 'http://example.com/Images/000123.png',
                'Achievements' => $achievements,
                'Leaderboards' => [],
            ],
        ];
    }

    private function createSampleAchievement(int $id, string $title, string $description): array
    {
        return [
            'ID' => $id,
            'Title' => $title,
            'Description' => $description,
            'MemAddr' => '0x000000',
            'Points' => 10,
            'Author' => 'TestAuthor',
            'Modified' => Carbon::now()->unix(),
            'Created' => Carbon::now()->subDays(5)->unix(),
            'BadgeName' => '00001',
            'Flags' => 3,
            'Type' => 'progression',
            'Rarity' => 25.5,
            'RarityHardcore' => 15.2,
            'BadgeURL' => 'http://example.com/Badge/00001.png',
            'BadgeLockedURL' => 'http://example.com/Badge/00001_lock.png',
        ];
    }

    public function testItReturnsUnmodifiedResponseWhenNoEventAchievements(): void
    {
        // Arrange
        $achievements = [
            $this->createSampleAchievement(1, 'Test Achievement 1', 'Test Description 1'),
            $this->createSampleAchievement(2, 'Test Achievement 2', 'Test Description 2'),
        ];

        $response = $this->createSamplePatchResponse($achievements);

        // Act
        $result = (new InjectPatchAotwEventDataAction())->execute($response);

        // Assert
        $this->assertEquals($response, $result);
        $this->assertEquals('Test Description 1', $result['PatchData']['Achievements'][0]['Description']);
        $this->assertEquals('Test Description 2', $result['PatchData']['Achievements'][1]['Description']);
    }

    public function testItAddsAchievementOfTheWeekLabel(): void
    {
        // Arrange
        // ... create a game with "of the week" in the title ...
        $game = Game::factory()->create(['Title' => 'Achievement of the Week']);

        $sourceAchievement = Achievement::factory()->published()->create([
            'ID' => 48615,
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        $eventAchievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        EventAchievement::factory()->create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
            'active_from' => Carbon::now()->subDays(3),
            'active_until' => Carbon::now()->addDays(4), // !! less than 20 days -> AOTW
        ]);

        $achievements = [
            $this->createSampleAchievement(48615, 'Shadow Wood', 'Enter the second hub Shadow Wood'),
            $this->createSampleAchievement(2, 'Test Achievement 2', 'Test Description 2'),
        ];

        $response = $this->createSamplePatchResponse($achievements);

        // Act
        $result = (new InjectPatchAotwEventDataAction())->execute($response);

        // Assert
        $this->assertEquals('[Achievement of the Week] Enter the second hub Shadow Wood', $result['PatchData']['Achievements'][0]['Description']);
        $this->assertEquals('Test Description 2', $result['PatchData']['Achievements'][1]['Description']);
    }

    public function testItAddsAchievementOfTheMonthLabel(): void
    {
        // Arrange
        // ... create a game with "of the week" in the title ...
        $game = Game::factory()->create(['Title' => 'Achievement of the Week']);

        $sourceAchievement = Achievement::factory()->published()->create([
            'ID' => 12345,
            'GameID' => $game->id,
            'Title' => 'Something\'s Fishy',
            'Description' => 'Complete the fishing quest',
        ]);

        $eventAchievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Title' => 'Something\'s Fishy',
            'Description' => 'Complete the fishing quest',
        ]);

        EventAchievement::factory()->create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
            'active_from' => Carbon::now()->subDays(10),
            'active_until' => Carbon::now()->addDays(25), // !! more than 20 days -> AOTM
        ]);

        $achievements = [
            $this->createSampleAchievement(1, 'Test Achievement 1', 'Test Description 1'),
            $this->createSampleAchievement(12345, 'Something\'s Fishy', 'Complete the fishing quest'),
        ];

        $response = $this->createSamplePatchResponse($achievements);

        // Act
        $result = (new InjectPatchAotwEventDataAction())->execute($response);

        // Assert
        $this->assertEquals('Test Description 1', $result['PatchData']['Achievements'][0]['Description']);
        $this->assertEquals('[Achievement of the Month] Complete the fishing quest', $result['PatchData']['Achievements'][1]['Description']);
    }

    public function testItProcessesAchievementsInSets(): void
    {
        // Arrange
        // ... create a game with "of the week" in the title ...
        $game = Game::factory()->create(['Title' => 'Achievement of the Week']);

        $sourceAchievement = Achievement::factory()->published()->create([
            'ID' => 48615,
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        $eventAchievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        EventAchievement::factory()->create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
            'active_from' => Carbon::now()->subDays(3),
            'active_until' => Carbon::now()->addDays(4),
        ]);

        // ... create a response that contains multiple sets ...
        $achievements = [
            $this->createSampleAchievement(1, 'Test Achievement 1', 'Test Description 1'),
        ];
        $setAchievements = [
            $this->createSampleAchievement(48615, 'Shadow Wood', 'Enter the second hub Shadow Wood'),
        ];

        $response = $this->createSamplePatchResponse($achievements);
        $response['PatchData']['Sets'] = [
            [
                'GameAchievementSetID' => 1,
                'SetTitle' => 'Bonus Set',
                'Type' => 'bonus',
                'ImageIcon' => '/Images/000124.png',
                'ImageIconURL' => 'http://example.com/Images/000124.png',
                'Achievements' => $setAchievements,
                'Leaderboards' => [],
            ],
        ];

        // Act
        $result = (new InjectPatchAotwEventDataAction())->execute($response);

        // Assert
        // ... the main achievement list should be unchanged ...
        $this->assertEquals('Test Description 1', $result['PatchData']['Achievements'][0]['Description']);

        // ... the achievement in the set should have the AOTW label ...
        $this->assertEquals(
            '[Achievement of the Week] Enter the second hub Shadow Wood',
            $result['PatchData']['Sets'][0]['Achievements'][0]['Description']
        );
    }

    public function testItHandlesBothAotwAndAotmAtTheSameTime(): void
    {
        // Arrange
        // ... create a game with "of the week" in the title ...
        $game = Game::factory()->create(['Title' => 'Achievement of the Week']);

        $aotwSourceAchievement = Achievement::factory()->published()->create([
            'ID' => 48615,
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        $aotwEventAchievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        EventAchievement::factory()->create([
            'achievement_id' => $aotwEventAchievement->id,
            'source_achievement_id' => $aotwSourceAchievement->id,
            'active_from' => Carbon::now()->subDays(3),
            'active_until' => Carbon::now()->addDays(4), // !! less than 20 days -> AOTW
        ]);

        $aotmSourceAchievement = Achievement::factory()->published()->create([
            'ID' => 12345,
            'GameID' => $game->id,
            'Title' => 'Something\'s Fishy',
            'Description' => 'Complete the fishing quest',
        ]);

        $aotmEventAchievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Title' => 'Something\'s Fishy',
            'Description' => 'Complete the fishing quest',
        ]);

        EventAchievement::factory()->create([
            'achievement_id' => $aotmEventAchievement->id,
            'source_achievement_id' => $aotmSourceAchievement->id,
            'active_from' => Carbon::now()->subDays(10),
            'active_until' => Carbon::now()->addDays(25), // !! more than 20 days -> AOTM
        ]);

        // ... create a response which contains both achievements ...
        $achievements = [
            $this->createSampleAchievement(48615, 'Shadow Wood', 'Enter the second hub Shadow Wood'),
            $this->createSampleAchievement(12345, 'Something\'s Fishy', 'Complete the fishing quest'),
        ];

        $response = $this->createSamplePatchResponse($achievements);

        // Act
        $result = (new InjectPatchAotwEventDataAction())->execute($response);

        // Assert
        $this->assertEquals('[Achievement of the Week] Enter the second hub Shadow Wood', $result['PatchData']['Achievements'][0]['Description']);
        $this->assertEquals('[Achievement of the Month] Complete the fishing quest', $result['PatchData']['Achievements'][1]['Description']);
    }

    public function testItHandlesEmptyAchievementsList(): void
    {
        // Arrange
        // ... create a game with "of the week" in the title ...
        $game = Game::factory()->create(['Title' => 'Achievement of the Week']);

        $sourceAchievement = Achievement::factory()->published()->create([
            'ID' => 48615,
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        $eventAchievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        EventAchievement::factory()->create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
            'active_from' => Carbon::now()->subDays(3),
            'active_until' => Carbon::now()->addDays(4),
        ]);

        $response = $this->createSamplePatchResponse([]);

        // Act
        $result = (new InjectPatchAotwEventDataAction())->execute($response);

        // Assert
        $this->assertEquals($response, $result); // !! should be unchanged
    }

    public function testItHandlesResponseWithoutAchievements(): void
    {
        // Arrange
        // ... create a game with "of the week" in the title ...
        $game = Game::factory()->create(['Title' => 'Achievement of the Week']);

        $sourceAchievement = Achievement::factory()->published()->create([
            'ID' => 48615,
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        $eventAchievement = Achievement::factory()->published()->create([
            'GameID' => $game->id,
            'Title' => 'Shadow Wood',
            'Description' => 'Enter the second hub Shadow Wood',
        ]);

        EventAchievement::factory()->create([
            'achievement_id' => $eventAchievement->id,
            'source_achievement_id' => $sourceAchievement->id,
            'active_from' => Carbon::now()->subDays(3),
            'active_until' => Carbon::now()->addDays(4),
        ]);

        $response = [
            'Success' => true,
            'PatchData' => [
                'ID' => 123,
                'Title' => 'Test Game',
                // !! no 'Achievements' key
            ],
        ];

        // Act
        $result = (new InjectPatchAotwEventDataAction())->execute($response);

        // Assert
        $this->assertEquals($response, $result); // !! should be unchanged
    }
}
