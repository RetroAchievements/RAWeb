<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class GameHashesTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->get("/api/v2/games/{$game->id}/hashes");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItFetchesHashesForGame(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $hash1 = GameHash::factory()->create(['game_id' => $game->id, 'system_id' => $system->id]);
        $hash2 = GameHash::factory()->create(['game_id' => $game->id, 'system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains((string) $hash1->id, $ids);
        $this->assertContains((string) $hash2->id, $ids);
    }

    public function testItReturns404WhenGameDoesNotExist(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/games/99999/hashes');

        // Assert
        $response->assertNotFound();
    }

    public function testItReturnsCorrectAttributes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $hash = GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'md5' => 'abc123def456',
            'name' => 'Super Mario Bros (USA).nes',
            'labels' => 'nointro,redump',
            'compatibility' => GameHashCompatibility::Compatible,
            'patch_url' => null,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');

        $this->assertEquals('abc123def456', $attributes['raMd5']);
        $this->assertEquals('Super Mario Bros (USA).nes', $attributes['name']);
        $this->assertEquals(['nointro', 'redump'], $attributes['labels']);
        $this->assertEquals('compatible', $attributes['compatibility']);
        $this->assertNull($attributes['patchUrl']);
        $this->assertArrayHasKey('createdAt', $attributes);
        $this->assertArrayHasKey('updatedAt', $attributes);
    }

    public function testItParsesLabelsAsArray(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'labels' => 'nointro',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals(['nointro'], $response->json('data.0.attributes.labels'));
    }

    public function testItReturnsEmptyArrayWhenLabelsIsNull(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'labels' => null,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals([], $response->json('data.0.attributes.labels'));
    }

    public function testItCanFilterByCompatibility(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $compatibleHash = GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::Incompatible,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes?filter[compatibility]=compatible");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $compatibleHash->id, $data[0]['id']);
    }

    public function testItCanFilterByMultipleCompatibilityValues(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $compatibleHash = GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        $patchRequiredHash = GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::PatchRequired,
        ]);
        GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::Incompatible,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes?filter[compatibility]=compatible,patch-required");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->toArray();
        $this->assertContains((string) $compatibleHash->id, $ids);
        $this->assertContains((string) $patchRequiredHash->id, $ids);
    }

    public function testItReturnsErrorWhenFilteringByInvalidCompatibility(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        GameHash::factory()->create(['game_id' => $game->id, 'system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes?filter[compatibility]=invalid-value");

        // Assert
        $response->assertStatus(400);
    }

    public function testItDoesNotIncludeSelfLinks(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        GameHash::factory()->create(['game_id' => $game->id, 'system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes");

        // Assert
        $response->assertSuccessful();
        $this->assertArrayNotHasKey('links', $response->json('data.0'));
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        GameHash::factory()->count(75)->create(['game_id' => $game->id, 'system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(50, $response->json('data'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
        $this->assertEquals(75, $response->json('meta.page.total'));
    }

    public function testItCanIncludeGameRelationship(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'title' => 'Super Mario Bros.',
            'system_id' => $system->id,
        ]);

        GameHash::factory()->create(['game_id' => $game->id, 'system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes?include=game");

        // Assert
        $response->assertSuccessful();
        $included = $response->json('included');
        $this->assertNotEmpty($included);
        $this->assertEquals('games', $included[0]['type']);
        $this->assertEquals((string) $game->id, $included[0]['id']);
        $this->assertEquals('Super Mario Bros.', $included[0]['attributes']['title']);
    }

    public function testItDoesNotReturnHashesFromOtherGames(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game1 = Game::factory()->create(['system_id' => $system->id]);
        $game2 = Game::factory()->create(['system_id' => $system->id]);

        $hash1 = GameHash::factory()->create(['game_id' => $game1->id, 'system_id' => $system->id]);
        GameHash::factory()->create(['game_id' => $game2->id, 'system_id' => $system->id]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game1->id}/hashes");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $hash1->id, $data[0]['id']);
    }

    public function testItExcludesSoftDeletedHashes(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $activeHash = GameHash::factory()->create(['game_id' => $game->id, 'system_id' => $system->id]);
        GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'deleted_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals((string) $activeHash->id, $data[0]['id']);
    }

    public function testItReturnsPatchUrlWhenPresent(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::PatchRequired,
            'patch_url' => 'https://github.com/RetroAchievements/RAPatches/raw/main/NES/1234-MyPatch.zip',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('game-hashes')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/hashes");

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');
        $this->assertEquals(
            'https://github.com/RetroAchievements/RAPatches/raw/main/NES/1234-MyPatch.zip',
            $attributes['patchUrl']
        );
    }
}
