<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Models\AchievementSet;
use App\Models\AchievementSetVersion;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;
use Tests\TestCase;

class AchievementSetVersionsTest extends TestCase
{
    use MakesJsonApiRequests;
    use RefreshDatabase;
    use TestsJsonApiIndex;

    protected function resourceType(): string
    {
        return 'achievement-set-versions';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/achievement-set-versions';
    }

    protected function createResource(): Model
    {
        $achievementSet = AchievementSet::factory()->create();

        return AchievementSetVersion::factory()->create([
            'version' => 1,
            'achievement_set_id' => $achievementSet->id,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
        ]);
    }

    /**
     * @return array{achievementSet: AchievementSet}
     */
    private function makeContext(): array
    {
        $achievementSet = AchievementSet::factory()->create();

        return ['achievementSet' => $achievementSet];
    }

    public function testItRequiresAuthentication(): void
    {
        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-versions')
            ->get('/api/v2/achievement-set-versions');

        // Assert
        $response->assertUnauthorized();
    }

    public function testItHasNoShowRoute(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievementSet' => $achievementSet] = $this->makeContext();

        $version = AchievementSetVersion::factory()->create([
            'version' => 1,
            'achievement_set_id' => $achievementSet->id,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-versions')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-set-versions/$version->id");

        // Assert
        $response->assertStatus(404);
    }

    public function testItSupportsEverySortableField(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievementSet' => $achievementSet] = $this->makeContext();
        AchievementSetVersion::factory()->create([
            'version' => 1,
            'achievement_set_id' => $achievementSet->id,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
        ]);
        AchievementSetVersion::factory()->create([
            'version' => 2,
            'achievement_set_id' => $achievementSet->id,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
        ]);

        $sortFields = [
            'id',
            'createdAt',
            'updatedAt',
        ];

        // Assert
        foreach ($sortFields as $sortField) {
            $this->jsonApi('v2')
                ->expects('achievement-set-versions')
                ->withHeader('X-API-Key', 'test-key')
                ->get("/api/v2/achievement-set-versions?sort={$sortField}")
                ->assertSuccessful();

            $this->jsonApi('v2')
                ->expects('achievement-set-versions')
                ->withHeader('X-API-Key', 'test-key')
                ->get("/api/v2/achievement-set-versions?sort=-{$sortField}")
                ->assertSuccessful();
        }
    }

    public function testItFiltersByAchievementSetId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievementSet' => $achievementSet] = $this->makeContext();

        $achievementSetA = AchievementSet::factory()->create();
        $achievementSetB = AchievementSet::factory()->create();

        $achievementSetVersionA = AchievementSetVersion::factory()->create([
            'achievement_set_id' => $achievementSetA->id,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
            'version' => 1,
        ]);

        AchievementSetVersion::factory()->create([
            'achievement_set_id' => $achievementSetB->id,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
            'version' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-versions')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-set-versions?filter[achievementSetId]={$achievementSetA->id}");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-versions', 'id' => (string) $achievementSetVersionA->id],
        ]);
    }

    public function testItIncludesAchievementSetWhenRequested(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['achievementSet' => $achievementSet] = $this->makeContext();
        AchievementSetVersion::factory()->create([
            'achievement_set_id' => $achievementSet,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
            'version' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-versions')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-versions?include=achievementSet');

        // Assert
        $response->assertSuccessful();

        $relationships = $response->json('data.0.relationships');
        $this->assertEquals('achievement-sets', $relationships['achievementSet']['data']['type']);
        $this->assertEquals((string) $achievementSet->id, $relationships['achievementSet']['data']['id']);

        $included = collect($response->json('included'));
        $this->assertTrue($included->contains(fn (array $r) => $r['type'] === 'achievement-sets' && $r['id'] === (string) $achievementSet->id));
    }

    public function testItScopesAchievementSetRelationshipEndpointToThatSet(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $achievementSetA = AchievementSet::factory()->create();
        $achievementSetB = AchievementSet::factory()->create();

        $achievementSetVersionA = AchievementSetVersion::factory()->create([
            'achievement_set_id' => $achievementSetA->id,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
            'version' => 1,
        ]);

        AchievementSetVersion::factory()->create([
            'achievement_set_id' => $achievementSetB->id,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
            'version' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-versions')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-sets/{$achievementSetA->id}/achievement-set-versions");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-versions', 'id' => (string) $achievementSetVersionA->id],
        ]);
    }

    public function testItCanIncludeAssociatedSetGames(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $game = Game::factory()->create();
        $achievementSet = AchievementSet::factory()->create();

        GameAchievementSet::factory()->create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => AchievementSetType::Core,
        ]);

        AchievementSetVersion::factory()->create([
            'achievement_set_id' => $achievementSet->id,
            'players_total' => 0,
            'players_hardcore' => 0,
            'achievements_published' => 0,
            'achievements_unpublished' => 0,
            'points_total' => 0,
            'version' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-versions')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-versions?include=achievementSet.games');

        // Assert
        $response->assertSuccessful();

        $included = collect($response->json('included'));

        $this->assertTrue($included->contains(
            fn (array $resource) => $resource['type'] === 'achievement-sets' && $resource['id'] === (string) $achievementSet->id
        ));
        $this->assertTrue($included->contains(
            fn (array $resource) => $resource['type'] === 'games' && $resource['id'] === (string) $game->id
        ));
    }
}
