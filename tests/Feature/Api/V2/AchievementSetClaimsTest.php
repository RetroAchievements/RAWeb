<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\Feature\Api\V2\Concerns\TestsJsonApiIndex;
use Tests\TestCase;

class AchievementSetClaimsTest extends TestCase
{
    use MakesJsonApiRequests;
    use RefreshDatabase;
    use TestsJsonApiIndex;

    protected function resourceType(): string
    {
        return 'achievement-set-claims';
    }

    protected function resourceEndpoint(): string
    {
        return '/api/v2/achievement-set-claims';
    }

    protected function createResource(): Model
    {
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        return AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);
    }

    /**
     * @return array{user: User, system: System, game: Game}
     */
    private function makeContext(): array
    {
        $user = User::factory()->create([
            'display_name' => 'ClaimDev',
            'username' => 'ClaimDev',
        ]);
        $system = System::factory()->create(['name' => 'Test System']);
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Test Game',
        ]);

        return ['user' => $user, 'system' => $system, 'game' => $game];
    }

    public function testItRequiresAuthentication(): void
    {
        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->get('/api/v2/achievement-set-claims');

        // Assert
        $response->assertUnauthorized();
    }

    public function testItHasNoShowRoute(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-set-claims/{$claim->id}");

        // Assert
        $response->assertStatus(404);
    }

    public function testItListsClaimsWithEmbeddedContext(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'system' => $system, 'game' => $game] = $this->makeContext();

        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Active,
            'claim_type' => ClaimType::Primary,
            'set_type' => ClaimSetType::NewSet,
            'special_type' => ClaimSpecial::None,
            'extensions_count' => 1,
            'finished_at' => Carbon::parse('2026-08-01 12:00:00'),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $claim->id],
        ]);

        $attributes = $response->json('data.0.attributes');

        $this->assertEquals('active', $attributes['status']);
        $this->assertEquals('primary', $attributes['claimType']);
        $this->assertEquals('new-set', $attributes['setType']);
        $this->assertEquals('none', $attributes['specialType']);
        $this->assertEquals(1, $attributes['extensionsCount']);

        $this->assertEquals($user->ulid, $attributes['userId']);
        $this->assertEquals('ClaimDev', $attributes['userDisplayName']);
        $this->assertEquals($game->id, $attributes['gameId']);
        $this->assertEquals('Test Game', $attributes['gameTitle']);
        $this->assertEquals($system->id, $attributes['systemId']);
        $this->assertEquals('Test System', $attributes['systemName']);
        $this->assertNotNull($attributes['gameIconUrl']);

        // ... relationships are not emitted unless explicitly included ...
        $this->assertArrayNotHasKey('relationships', $response->json('data.0'));

        // ... no self-link for relationship-style resources ...
        $this->assertArrayNotHasKey('links', $response->json('data.0'));
    }

    public function testItHyphenatesMultiWordEnumValues(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::InReview,
            'claim_type' => ClaimType::Collaboration,
            'set_type' => ClaimSetType::Revision,
            'special_type' => ClaimSpecial::OwnRevision,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims');

        // Assert
        $response->assertSuccessful();
        $attributes = $response->json('data.0.attributes');
        $this->assertEquals('in-review', $attributes['status']);
        $this->assertEquals('collaboration', $attributes['claimType']);
        $this->assertEquals('revision', $attributes['setType']);
        $this->assertEquals('own-revision', $attributes['specialType']);
    }

    public function testItDefaultsToClaimedAtDescending(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        $oldClaim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'created_at' => '2024-01-01 00:00:00',
        ]);
        $newClaim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'created_at' => '2026-01-01 00:00:00',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals([(string) $newClaim->id, (string) $oldClaim->id], $ids);
    }

    public function testItSupportsEverySortableField(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();
        AchievementSetClaim::factory()->count(2)->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $sortFields = [
            'claimedAt',
            'claimType',
            'extensionsCount',
            'finishedAt',
            'gameTitle',
            'setType',
            'specialType',
            'status',
            'updatedAt',
            'userDisplayName',
        ];

        // Assert
        foreach ($sortFields as $sortField) {
            $this->jsonApi('v2')
                ->expects('achievement-set-claims')
                ->withHeader('X-API-Key', 'test-key')
                ->get("/api/v2/achievement-set-claims?sort={$sortField}")
                ->assertSuccessful();

            $this->jsonApi('v2')
                ->expects('achievement-set-claims')
                ->withHeader('X-API-Key', 'test-key')
                ->get("/api/v2/achievement-set-claims?sort=-{$sortField}")
                ->assertSuccessful();
        }
    }

    public function testItSortsByGameTitle(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create();
        $system = System::factory()->create();

        $gameAlpha = Game::factory()->create(['system_id' => $system->id, 'title' => 'Alpha']);
        $gameZulu = Game::factory()->create(['system_id' => $system->id, 'title' => 'Zulu']);

        $alphaClaim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $gameAlpha->id,
        ]);
        $zuluClaim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $gameZulu->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?sort=gameTitle');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals([(string) $alphaClaim->id, (string) $zuluClaim->id], $ids);
    }

    public function testItSortsByUserDisplayName(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        $aaron = User::factory()->create(['display_name' => 'Aaron', 'username' => 'Aaron']);
        $zoe = User::factory()->create(['display_name' => 'Zoe', 'username' => 'Zoe']);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $aaronClaim = AchievementSetClaim::factory()->create([
            'user_id' => $aaron->id,
            'game_id' => $game->id,
        ]);
        $zoeClaim = AchievementSetClaim::factory()->create([
            'user_id' => $zoe->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?sort=userDisplayName');

        // Assert
        $response->assertSuccessful();
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals([(string) $aaronClaim->id, (string) $zoeClaim->id], $ids);
    }

    public function testItFiltersByStatus(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        $activeClaim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Active,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Complete,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[status]=active');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $activeClaim->id],
        ]);
    }

    public function testItSupportsCommaDelimitedStatusFilter(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Active,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::InReview,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Complete,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[status]=active,in-review');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(2, $response->json('data'));
    }

    public function testItRejectsUnknownStatusFilterValue(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[status]=bogus');

        // Assert
        $response->assertStatus(400);
    }

    public function testItFiltersByClaimType(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        $primary = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'claim_type' => ClaimType::Primary,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'claim_type' => ClaimType::Collaboration,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[claimType]=primary');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $primary->id],
        ]);
    }

    public function testItRejectsUnknownClaimTypeFilterValue(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[claimType]=bogus');

        // Assert
        $response->assertStatus(400);
    }

    public function testItFiltersBySetType(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        $newSet = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'set_type' => ClaimSetType::NewSet,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'set_type' => ClaimSetType::Revision,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[setType]=new-set');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $newSet->id],
        ]);
    }

    public function testItRejectsUnknownSetTypeFilterValue(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[setType]=bogus');

        // Assert
        $response->assertStatus(400);
    }

    public function testItFiltersBySpecialType(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        $scheduled = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'special_type' => ClaimSpecial::ScheduledRelease,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'special_type' => ClaimSpecial::None,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[specialType]=scheduled-release');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $scheduled->id],
        ]);
    }

    public function testItRejectsUnknownSpecialTypeFilterValue(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[specialType]=bogus');

        // Assert
        $response->assertStatus(400);
    }

    public function testItFiltersByExpired(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        $expired = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'finished_at' => Carbon::now()->subDay(),
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'finished_at' => Carbon::now()->addMonths(3),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[expired]=true');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $expired->id],
        ]);
    }

    public function testExpiredFilterExcludesCompletedAndDroppedClaims(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        // ... active claim past its finished_at: this is what "expired" means ...
        $expiredActive = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Active,
            'finished_at' => Carbon::now()->subDay(),
        ]);

        // ... a completed claim from years ago should _not_ surface under "expired" ...
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Complete,
            'finished_at' => Carbon::parse('2020-01-01 00:00:00'),
        ]);

        // ... a dropped claim with a past finished_at should _not_ surface either ...
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Dropped,
            'finished_at' => Carbon::now()->subWeek(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[expired]=true');

        // Assert
        // ... only the active expired claim ...
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $expiredActive->id],
        ]);
    }

    public function testExpiredFilterIncludesInReviewClaims(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();

        $inReviewExpired = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::InReview,
            'finished_at' => Carbon::now()->subDay(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[expired]=true');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $inReviewExpired->id],
        ]);
    }

    public function testItRejectsUnknownExpiredFilterValue(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[expired]=maybe');

        // Assert
        $response->assertStatus(400);
    }

    public function testItFiltersByUserUlid(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $ownClaim = AchievementSetClaim::factory()->create([
            'user_id' => $owner->id,
            'game_id' => $game->id,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $other->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-set-claims?filter[user]={$owner->ulid}");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $ownClaim->id],
        ]);
    }

    public function testItFiltersByUserUsername(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $owner = User::factory()->create([
            'display_name' => 'TargetDev',
            'username' => 'TargetDev',
        ]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $owner->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[user]=TargetDev');

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $claim->id],
        ]);
    }

    public function testItReturnsEmptyForUnknownUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?filter[user]=NotAUser');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(0, $response->json('data'));
    }

    public function testItFiltersByGameId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create();
        $system = System::factory()->create();
        $gameA = Game::factory()->create(['system_id' => $system->id]);
        $gameB = Game::factory()->create(['system_id' => $system->id]);

        $claimA = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $gameA->id,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $gameB->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-set-claims?filter[gameId]={$gameA->id}");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $claimA->id],
        ]);
    }

    public function testItIncludesUserAndGameWhenRequested(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        ['user' => $user, 'game' => $game] = $this->makeContext();
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims?include=user,game');

        // Assert
        $response->assertSuccessful();

        $relationships = $response->json('data.0.relationships');
        $this->assertEquals('users', $relationships['user']['data']['type']);
        $this->assertEquals($user->ulid, $relationships['user']['data']['id']);
        $this->assertEquals('games', $relationships['game']['data']['type']);
        $this->assertEquals((string) $game->id, $relationships['game']['data']['id']);

        $included = collect($response->json('included'));
        $this->assertTrue($included->contains(fn (array $r) => $r['type'] === 'users' && $r['id'] === $user->ulid));
        $this->assertTrue($included->contains(fn (array $r) => $r['type'] === 'games' && $r['id'] === (string) $game->id));
    }

    public function testItScopesUserRelationshipEndpointToThatUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $ownClaim = AchievementSetClaim::factory()->create([
            'user_id' => $owner->id,
            'game_id' => $game->id,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $other->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$owner->ulid}/achievement-set-claims");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $ownClaim->id],
        ]);
    }

    public function testItHidesBannedUserClaimsFromGlobalIndex(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $bannedUser = User::factory()->create(['banned_at' => Carbon::now()]);
        $activeUser = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        AchievementSetClaim::factory()->create([
            'user_id' => $bannedUser->id,
            'game_id' => $game->id,
        ]);
        $activeClaim = AchievementSetClaim::factory()->create([
            'user_id' => $activeUser->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/achievement-set-claims');

        // Assert
        // ... only the active user's claim is returned ...
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $activeClaim->id],
        ]);
    }

    public function testItHidesBannedUserClaimsFromGameRelationshipEndpoint(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $bannedUser = User::factory()->create(['banned_at' => Carbon::now()]);
        $activeUser = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        AchievementSetClaim::factory()->create([
            'user_id' => $bannedUser->id,
            'game_id' => $game->id,
        ]);
        $activeClaim = AchievementSetClaim::factory()->create([
            'user_id' => $activeUser->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$game->id}/achievement-set-claims");

        // Assert
        // ... only the active user's claim is returned ...
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $activeClaim->id],
        ]);
    }

    public function testItReturns404ForBannedUserRelationshipEndpoint(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $bannedUser = User::factory()->create(['banned_at' => Carbon::now()]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        AchievementSetClaim::factory()->create([
            'user_id' => $bannedUser->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$bannedUser->ulid}/achievement-set-claims");

        // Assert
        $response->assertStatus(404);
    }

    public function testItHidesBannedUserClaimsFromUserFilter(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $bannedUser = User::factory()->create([
            'banned_at' => Carbon::now(),
            'display_name' => 'BannedDev',
            'username' => 'BannedDev',
        ]);
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        AchievementSetClaim::factory()->create([
            'user_id' => $bannedUser->id,
            'game_id' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/achievement-set-claims?filter[user]={$bannedUser->ulid}");

        // Assert
        // ... banned user's claims should not surface even via direct filter ...
        $response->assertSuccessful();
        $this->assertEquals([], $response->json('data'));
    }

    public function testItScopesGameRelationshipEndpointToThatGame(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $user = User::factory()->create();
        $system = System::factory()->create();
        $gameA = Game::factory()->create(['system_id' => $system->id]);
        $gameB = Game::factory()->create(['system_id' => $system->id]);

        $claimA = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $gameA->id,
        ]);
        AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $gameB->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('achievement-set-claims')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/games/{$gameA->id}/achievement-set-claims");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'achievement-set-claims', 'id' => (string) $claimA->id],
        ]);
    }
}
