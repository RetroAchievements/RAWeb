<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Community\Enums\AwardType;
use App\Models\EventAward;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\SiteAward;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Tests\TestCase;

class UserAwardsTest extends TestCase
{
    use RefreshDatabase;
    use MakesJsonApiRequests;

    public function testItRequiresAuthentication(): void
    {
        // Arrange
        $player = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_key' => $game->id,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->get("/api/v2/users/{$player->ulid}/awards");

        // Assert
        $response->assertUnauthorized();
    }

    public function testItFetchesVisibleAwardsWithMeta(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Awarded Game',
        ]);

        $award = PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards");

        // Assert
        $response->assertSuccessful();
        $response->assertFetchedMany([
            ['type' => 'user-awards', 'id' => (string) $award->id],
        ]);

        $data = $response->json('data.0');

        $this->assertEquals('mastered', $data['attributes']['kind']);
        $this->assertEquals('Awarded Game', $data['attributes']['title']);
        $this->assertEquals($game->id, $data['attributes']['context']['gameId']);
        $this->assertEquals('hardcore', $data['attributes']['context']['mode']);
        $this->assertEquals((string) $game->id, $data['relationships']['game']['data']['id']);

        $this->assertEquals(1, $response->json('meta.totalAwardsCount'));
        $this->assertEquals(0, $response->json('meta.hiddenAwardsCount'));
        $this->assertEquals(1, $response->json('meta.masteryAwardsCount'));
        $this->assertEquals(0, $response->json('meta.completionAwardsCount'));
    }

    public function testItCanIncludeAssociatedGame(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Softcore,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?include=game");

        // Assert
        $response->assertSuccessful();

        $included = collect($response->json('included'));

        $this->assertTrue($included->contains(fn (array $resource) => $resource['type'] === 'games' && $resource['id'] === (string) $game->id));
    }

    public function testItCanIncludeAssociatedGameSystem(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create(['name' => 'Nintendo 64']);
        $game = Game::factory()->create(['system_id' => $system->id]);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Softcore,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?include=game.system");

        // Assert
        $response->assertSuccessful();

        $included = collect($response->json('included'));

        $this->assertTrue($included->contains(
            fn (array $resource) => $resource['type'] === 'games' && $resource['id'] === (string) $game->id
        ));
        $this->assertTrue($included->contains(
            fn (array $resource) => $resource['type'] === 'systems' && $resource['id'] === (string) $system->id
        ));
    }

    public function testItCanIncludeAssociatedEventsAndExposeWhetherTheyGrantSiteAwards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();

        $legacyGameA = Game::factory()->create(['title' => 'Community Event']);
        $legacyGameB = Game::factory()->create(['title' => 'Developer Event']);

        $eventAwardA = EventAward::factory()->create([
            'tier_index' => 2,
            'image_asset_path' => '/Images/event-a-tier.png',
        ]);
        $eventA = $eventAwardA->event()->first();
        $eventA->legacy_game_id = $legacyGameA->id;
        $eventA->gives_site_award = false;
        $eventA->image_asset_path = '/Images/event-a.png';
        $eventA->save();

        $eventAwardB = EventAward::factory()->create([
            'tier_index' => 1,
            'image_asset_path' => '/Images/event-b-tier.png',
        ]);
        $eventB = $eventAwardB->event()->first();
        $eventB->legacy_game_id = $legacyGameB->id;
        $eventB->gives_site_award = true;
        $eventB->image_asset_path = '/Images/event-b.png';
        $eventB->save();

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Event,
            'award_key' => $eventA->id,
            'award_tier' => 2,
            'order_column' => 0,
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Event,
            'award_key' => $eventB->id,
            'award_tier' => 1,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?include=event");

        // Assert
        $response->assertSuccessful();

        $data = collect($response->json('data'));
        $included = collect($response->json('included'));

        $this->assertSame(['event', 'event'], $data->pluck('attributes.kind')->all());
        $this->assertSame([false, true], $data->pluck('attributes.context.grantsSiteAward')->all());
        $this->assertTrue($included->contains(fn (array $resource) => $resource['type'] === 'events' && $resource['id'] === (string) $eventA->id));
        $this->assertTrue($included->contains(fn (array $resource) => $resource['type'] === 'events' && $resource['id'] === (string) $eventB->id));
        $this->assertEquals(1, $response->json('meta.eventAwardsCount'));
        $this->assertEquals(1, $response->json('meta.siteAwardsCount'));
    }

    public function testItCanIncludeAssociatedEventAwards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();

        $legacyGame = Game::factory()->create(['title' => 'Community Event']);

        $eventAward = EventAward::factory()->create([
            'tier_index' => 2,
            'label' => 'Gold',
        ]);
        $event = $eventAward->event()->first();
        $event->legacy_game_id = $legacyGame->id;
        $event->gives_site_award = false;
        $event->save();

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Event,
            'award_key' => $event->id,
            'award_tier' => 2,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?include=event.awards");

        // Assert
        $response->assertSuccessful();

        $included = collect($response->json('included'));

        $this->assertTrue($included->contains(
            fn (array $resource) => $resource['type'] === 'events' && $resource['id'] === (string) $event->id
        ));
        $this->assertTrue($included->contains(
            fn (array $resource) => $resource['type'] === 'event-awards' && $resource['id'] === (string) $eventAward->id
        ));
    }

    public function testItUsesTheSiteAwardLabelAsThePlaytestTitle(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $siteAward = SiteAward::query()->create([
            'award_type' => AwardType::Playtest,
            'label' => 'Very Active Playtester',
            'image_asset_path' => '/Images/playtest-badge.png',
        ]);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Playtest,
            'award_key' => $siteAward->id,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards");

        // Assert
        $response->assertSuccessful();
        $this->assertEquals('playtest', $response->json('data.0.attributes.kind'));
        $this->assertEquals('Very Active Playtester', $response->json('data.0.attributes.title'));
        $this->assertEquals($siteAward->id, $response->json('data.0.attributes.context.siteAwardId'));
    }

    public function testItCanSortByAwardedAtDescending(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $gameA = Game::factory()->create(['system_id' => $system->id, 'title' => 'Older']);
        $gameB = Game::factory()->create(['system_id' => $system->id, 'title' => 'Newer']);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameA->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 1,
            'awarded_at' => '2024-01-01 12:00:00',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameB->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
            'awarded_at' => '2024-06-15 12:00:00',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?sort=-awardedAt");

        // Assert
        $response->assertSuccessful();

        $titles = collect($response->json('data'))->pluck('attributes.title')->all();
        $this->assertSame(['Newer', 'Older'], $titles);
    }

    public function testItCanFilterByKind(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $gameA = Game::factory()->create(['system_id' => $system->id, 'title' => 'Mastered Game']);
        $gameB = Game::factory()->create(['system_id' => $system->id, 'title' => 'Beaten Game']);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameA->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $gameB->id,
            'award_tier' => UnlockMode::Softcore,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[kind]=mastered");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('mastered', $response->json('data.0.attributes.kind'));
        $this->assertEquals('Mastered Game', $response->json('data.0.attributes.title'));
    }

    public function testItCanFilterByGameId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $gameA = Game::factory()->create(['system_id' => $system->id]);
        $gameB = Game::factory()->create(['system_id' => $system->id]);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameA->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $gameB->id,
            'award_tier' => UnlockMode::Softcore,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[gameId]={$gameB->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $gameB->id, $response->json('data.0.relationships.game.data.id'));
    }

    public function testItCanFilterByEventId(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();

        $legacyGameA = Game::factory()->create(['title' => 'Event A']);
        $legacyGameB = Game::factory()->create(['title' => 'Event B']);

        $eventAwardA = EventAward::factory()->create(['tier_index' => 2]);
        $eventA = $eventAwardA->event()->first();
        $eventA->legacy_game_id = $legacyGameA->id;
        $eventA->save();

        $eventAwardB = EventAward::factory()->create(['tier_index' => 1]);
        $eventB = $eventAwardB->event()->first();
        $eventB->legacy_game_id = $legacyGameB->id;
        $eventB->save();

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Event,
            'award_key' => $eventA->id,
            'award_tier' => 2,
            'order_column' => 0,
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Event,
            'award_key' => $eventB->id,
            'award_tier' => 1,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[eventId]={$eventB->id}");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $eventB->id, $response->json('data.0.relationships.event.data.id'));
    }

    public function testItCanFilterByAwardedDateRange(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $gameA = Game::factory()->create(['system_id' => $system->id]);
        $gameB = Game::factory()->create(['system_id' => $system->id]);
        $gameC = Game::factory()->create(['system_id' => $system->id]);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameA->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
            'awarded_at' => '2024-01-01 12:00:00',
        ]);
        $inRangeAward = PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameB->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 1,
            'awarded_at' => '2024-06-15 12:00:00',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameC->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 2,
            'awarded_at' => '2024-12-15 12:00:00',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[awardedFrom]=2024-03-01&filter[awardedTo]=2024-09-01");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $inRangeAward->id, $response->json('data.0.id'));
    }

    public function testItCorrectlyAppliesVisibility(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Softcore,
            'order_column' => 0,
            'awarded_at' => now()->subDay(),
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => -1, // !!
            'awarded_at' => now(),
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards");

        // Assert
        $response->assertSuccessful();

        $this->assertCount(0, $response->json('data'));
        $this->assertEquals(1, $response->json('meta.totalAwardsCount'));
        $this->assertEquals(1, $response->json('meta.hiddenAwardsCount')); // !!
        $this->assertEquals(1, $response->json('meta.masteryAwardsCount'));
        $this->assertEquals(0, $response->json('meta.completionAwardsCount'));
    }

    public function testItCollapsesDeveloperYieldAwardsToTheHighestTier(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::AchievementUnlocksYield,
            'award_key' => 1,
            'order_column' => 0,
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::AchievementUnlocksYield,
            'award_key' => 2,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards");

        // Assert
        $response->assertSuccessful();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('achievement-unlocks-yield', $response->json('data.0.attributes.kind'));
        $this->assertEquals(2, $response->json('data.0.attributes.context.tier'));
        $this->assertEquals(1, $response->json('meta.totalAwardsCount'));
    }

    public function testItPaginatesBy50ByDefault(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();

        $games = Game::factory()->count(60)->create(['system_id' => $system->id]);

        foreach ($games as $index => $game) {
            PlayerBadge::factory()->create([
                'user_id' => $player->id,
                'award_type' => AwardType::Mastery,
                'award_key' => $game->id,
                'award_tier' => UnlockMode::Hardcore,
                'order_column' => $index,
            ]);
        }

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards");

        // Assert
        $response->assertSuccessful();

        $this->assertCount(50, $response->json('data'));
        $this->assertEquals(50, $response->json('meta.page.perPage'));
    }

    public function testItReturns404ForNonexistentUser(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get('/api/v2/users/nonexistent-user/awards');

        // Assert
        $response->assertNotFound();
    }
}
