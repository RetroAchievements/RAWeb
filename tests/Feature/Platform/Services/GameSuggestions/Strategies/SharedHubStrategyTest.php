<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Models\GameSet;
use App\Platform\Enums\GameSetType;
use App\Platform\Enums\GameSuggestionReason;
use App\Platform\Services\GameSuggestions\Strategies\SharedHubStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedHubStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function testItSelectsGamesFromSharedHub(): void
    {
        // Arrange
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);
        $hubGame1 = Game::factory()->create(['achievements_published' => 10]);
        $hubGame2 = Game::factory()->create(['achievements_published' => 10]);
        $unrelatedGame = Game::factory()->create(['achievements_published' => 10]);

        // ... create a hub that contains our source game and potential suggestions ...
        $hub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'title' => '[Series - Sonic the Hedgehog]',
        ]);
        $hub->games()->attach([
            $sourceGame->id,
            $hubGame1->id,
            $hubGame2->id,
        ]);

        // Act
        $strategy = new SharedHubStrategy($sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNotNull($result);

        $this->assertTrue(in_array($result->id, [$hubGame1->id, $hubGame2->id]));
        $this->assertNotEquals($sourceGame->id, $result->id);
        $this->assertNotEquals($unrelatedGame->id, $result->id);

        $this->assertEquals(GameSuggestionReason::SharedHub, $strategy->reason());

        $context = $strategy->reasonContext();
        $this->assertNotNull($context);
        $this->assertEquals(
            $hub->id,
            $context->relatedGameSet->id
        );
        $this->assertEquals('[Series - Sonic the Hedgehog]', $context->relatedGameSet->title);
    }

    public function testItReturnsNullWhenNoSharedHubExists(): void
    {
        // Arrange
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);

        // Act
        $strategy = new SharedHubStrategy($sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNull($result);
    }

    public function testItRandomlySelectsFromMultipleHubs(): void
    {
        // Arrange
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);

        $hubCount = 10;
        $games = [];
        $hubs = [];
        for ($i = 0; $i < $hubCount; $i++) {
            $hubs[$i] = GameSet::factory()->create([
                'type' => GameSetType::Hub,
                'title' => $i === 0 ? '[Series - Sonic the Hedgehog]' : "[Hub {$i}]",
            ]);

            $games[$i] = Game::factory()->create(['achievements_published' => 10]);
            $hubs[$i]->games()->attach([$sourceGame->id, $games[$i]->id]);
        }

        $seenGames = [];
        $seenHubs = [];
        for ($i = 0; $i < 20; $i++) {
            $strategy = new SharedHubStrategy($sourceGame);
            $result = $strategy->select();

            if ($result) {
                $seenGames[] = $result->id;
                $context = $strategy->reasonContext();
                if ($context) {
                    $seenHubs[] = $context->relatedGameSet->id;
                }
            }
        }

        // Assert
        $this->assertGreaterThan(5, count(array_unique($seenGames)));
        $this->assertGreaterThan(5, count(array_unique($seenHubs)));
    }

    public function testItIgnoresHubsWithOnlyOneGame(): void
    {
        // Arrange
        $sourceGame = Game::factory()->create(['achievements_published' => 10]);

        // ... create a hub with only the source game ...
        $lonelyHub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'title' => 'Lonely Hub',
        ]);
        $lonelyHub->games()->attach([$sourceGame->id]);

        // ... create a hub with several games ...
        $popularHub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'title' => 'Popular Hub',
        ]);
        $otherGame = Game::factory()->create(['achievements_published' => 10]);
        $popularHub->games()->attach([$sourceGame->id, $otherGame->id]);

        // Act
        $strategy = new SharedHubStrategy($sourceGame);
        $result = $strategy->select();

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($otherGame->id, $result->id);

        $context = $strategy->reasonContext();
        $this->assertNotNull($context);
        $this->assertEquals('Popular Hub', $context->relatedGameSet->title);
    }
}
