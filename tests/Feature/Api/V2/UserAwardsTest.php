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
        $this->assertArrayNotHasKey('relationships', $data);

        $this->assertEquals(1, $response->json('meta.totalAwardsCount'));
        $this->assertEquals(0, $response->json('meta.hiddenAwardsCount'));
        $this->assertEquals(1, $response->json('meta.masteryAwardsCount'));
        $this->assertEquals(0, $response->json('meta.completionAwardsCount'));
    }

    public function testItPreservesV1GameAwardParityForBeatenAndMasteredAwards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Dragon Quest',
        ]);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Casual,
            'order_column' => 0,
            'awarded_at' => '2017-06-21 01:30:00',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
            'awarded_at' => '2017-06-21 01:44:52',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Casual,
            'order_column' => 0,
            'awarded_at' => '2017-06-21 02:00:00',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
            'awarded_at' => '2017-06-21 02:16:57',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards");

        // Assert
        $response->assertSuccessful();

        $kinds = collect($response->json('data'))->pluck('attributes.kind')->all();

        $this->assertSame(['beaten-hardcore', 'mastered'], $kinds);
        $this->assertEquals(2, $response->json('meta.totalAwardsCount'));
        $this->assertEquals(1, $response->json('meta.beatenHardcoreAwardsCount'));
        $this->assertEquals(0, $response->json('meta.beatenCasualAwardsCount'));
        $this->assertEquals(1, $response->json('meta.masteryAwardsCount'));
        $this->assertEquals(0, $response->json('meta.completionAwardsCount'));
    }

    public function testItCanCollapseGameAwardsToTheHighestAwardPerGame(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Dragon Quest',
        ]);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Casual,
            'order_column' => 0,
            'awarded_at' => '2017-06-21 01:30:00',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
            'awarded_at' => '2017-06-21 01:44:52',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Casual,
            'order_column' => 0,
            'awarded_at' => '2017-06-21 02:00:00',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
            'awarded_at' => '2017-06-21 02:16:57',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[gameAwardTier]=highest");

        // Assert
        $response->assertSuccessful();

        $this->assertSame(['mastered'], collect($response->json('data'))->pluck('attributes.kind')->all());
        $this->assertEquals(1, $response->json('meta.totalAwardsCount'));
        $this->assertEquals(0, $response->json('meta.beatenHardcoreAwardsCount'));
        $this->assertEquals(0, $response->json('meta.beatenCasualAwardsCount'));
        $this->assertEquals(1, $response->json('meta.masteryAwardsCount'));
        $this->assertEquals(0, $response->json('meta.completionAwardsCount'));
    }

    public function testHighestGameAwardTierOnlyComparesVisibleAwards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Hidden Mastery Game',
        ]);

        $visibleAward = PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
            'awarded_at' => '2024-06-15 12:00:00',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => -1,
            'awarded_at' => '2024-06-16 12:00:00',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[gameAwardTier]=highest");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $visibleAward->id, $response->json('data.0.id'));
        $this->assertEquals('beaten-hardcore', $response->json('data.0.attributes.kind'));
    }

    public function testHighestGameAwardTierOnlyComparesAwardsInsideTheFilteredDateRange(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create([
            'system_id' => $system->id,
            'title' => 'Date Range Game',
        ]);

        $inRangeAward = PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
            'awarded_at' => '2024-06-15 12:00:00',
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $game->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 1,
            'awarded_at' => '2024-12-15 12:00:00',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[awardedFrom]=2024-03-01&filter[awardedTo]=2024-09-01&filter[gameAwardTier]=highest");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $inRangeAward->id, $response->json('data.0.id'));
        $this->assertEquals('beaten-hardcore', $response->json('data.0.attributes.kind'));
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
            'award_tier' => UnlockMode::Casual,
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

        $this->assertEquals((string) $game->id, $response->json('data.0.relationships.game.data.id'));
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
            'award_tier' => UnlockMode::Casual,
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
            'display_award_tier' => 1,
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
        $this->assertSame([2, 1], $data->pluck('attributes.context.tierIndex')->all());
        $this->assertSame([1, 1], $data->pluck('attributes.context.displayTierIndex')->all());
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

    public function testItUsesTheDisplayTierBadgeUrlForEventAwards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $legacyGame = Game::factory()->create(['title' => 'Tiered Event']);

        // ... create the highest tier first so we can attach a second tier to the same event ...
        $highTierAward = EventAward::factory()->create([
            'tier_index' => 2,
            'image_asset_path' => '/Images/event-tier-2.png',
        ]);
        $event = $highTierAward->event()->first();
        $event->legacy_game_id = $legacyGame->id;
        $event->image_asset_path = '/Images/event.png';
        $event->save();

        EventAward::factory()->create([
            'event_id' => $event->id,
            'tier_index' => 1,
            'image_asset_path' => '/Images/event-tier-1.png',
        ]);

        // ... the user earned tier 2 but prefers to display tier 1 ...
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Event,
            'award_key' => $event->id,
            'award_tier' => 2,
            'display_award_tier' => 1,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards");

        // Assert
        $response->assertSuccessful();
        $this->assertStringEndsWith('/Images/event-tier-1.png', $response->json('data.0.attributes.badgeUrl'));
        $this->assertEquals(2, $response->json('data.0.attributes.context.tierIndex'));
        $this->assertEquals(1, $response->json('data.0.attributes.context.displayTierIndex'));
    }

    public function testItFallsBackToTheEarnedTierBadgeUrlForEventAwardsWithNoDisplayPreference(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $legacyGame = Game::factory()->create(['title' => 'Tiered Event']);

        $eventAward = EventAward::factory()->create([
            'tier_index' => 2,
            'image_asset_path' => '/Images/event-tier-2.png',
        ]);
        $event = $eventAward->event()->first();
        $event->legacy_game_id = $legacyGame->id;
        $event->image_asset_path = '/Images/event.png';
        $event->save();

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Event,
            'award_key' => $event->id,
            'award_tier' => 2,
            'display_award_tier' => null,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards");

        // Assert
        $response->assertSuccessful();
        $this->assertStringEndsWith('/Images/event-tier-2.png', $response->json('data.0.attributes.badgeUrl'));
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

    public function testItExposesMediaContributionAwards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::MediaContribution,
            'award_key' => 2,
            'award_tier' => 2,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[kind]=media-contribution");

        // Assert
        $response->assertSuccessful();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('media-contribution', $response->json('data.0.attributes.kind'));
        $this->assertEquals('Media Contribution', $response->json('data.0.attributes.title'));
        $this->assertStringEndsWith('/assets/images/badge/mediaContrib-2.png', $response->json('data.0.attributes.badgeUrl'));
        $this->assertEquals(2, $response->json('data.0.attributes.context.tier'));
        $this->assertEquals(2, $response->json('data.0.attributes.context.displayTierIndex'));
        $this->assertEquals(2, $response->json('data.0.attributes.context.earnedTier'));
        $this->assertEquals(100, $response->json('data.0.attributes.context.threshold'));
        $this->assertEquals(1, $response->json('meta.siteAwardsCount'));
    }

    public function testItUsesTheDisplayTierBadgeUrlForMediaContributionAwards(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::MediaContribution,
            'award_key' => 2,
            'award_tier' => 2,
            'display_award_tier' => 0,
            'order_column' => 0,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[kind]=media-contribution");

        // Assert
        $response->assertSuccessful();
        $this->assertStringEndsWith('/assets/images/badge/mediaContrib-0.png', $response->json('data.0.attributes.badgeUrl'));
        $this->assertEquals(0, $response->json('data.0.attributes.context.tier'));
        $this->assertEquals(0, $response->json('data.0.attributes.context.displayTierIndex'));
        $this->assertEquals(2, $response->json('data.0.attributes.context.earnedTier'));
        $this->assertEquals(10, $response->json('data.0.attributes.context.threshold'));
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
            'award_tier' => UnlockMode::Casual,
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

    public function testItCanFilterByBeatenCasualKind(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();
        $system = System::factory()->create();
        $beatenHardcoreGame = Game::factory()->create(['system_id' => $system->id, 'title' => 'Hardcore Beaten Game']);
        $beatenCasualGame = Game::factory()->create(['system_id' => $system->id, 'title' => 'Casual Beaten Game']);

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $beatenHardcoreGame->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 0,
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::GameBeaten,
            'award_key' => $beatenCasualGame->id,
            'award_tier' => UnlockMode::Casual,
            'order_column' => 1,
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[kind]=beaten-casual");

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('beaten-casual', $response->json('data.0.attributes.kind'));
        $this->assertEquals('Casual Beaten Game', $response->json('data.0.attributes.title'));
    }

    public function testItReturnsErrorWhenFilteringByInvalidKind(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[kind]=events");

        // Assert
        $response->assertStatus(400);
    }

    public function testItReturnsErrorWhenFilteringByInvalidGameAwardTierMode(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[gameAwardTier]=collapsed");

        // Assert
        $response->assertStatus(400);
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
            'award_tier' => UnlockMode::Casual,
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
        $this->assertEquals($gameB->id, $response->json('data.0.attributes.context.gameId'));
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
        $this->assertEquals($eventB->id, $response->json('data.0.attributes.context.eventId'));
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
        $gameD = Game::factory()->create(['system_id' => $system->id]);
        $gameE = Game::factory()->create(['system_id' => $system->id]);

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
        $lateFinalDayAward = PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameD->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 2,
            'awarded_at' => '2024-09-01 23:59:59', // late in the final day, a bare-date awardedTo bound must still return this
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameE->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 3,
            'awarded_at' => '2024-09-02 00:00:00', // midnight the day after the awardedTo bound, so it's always excluded
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::Mastery,
            'award_key' => $gameC->id,
            'award_tier' => UnlockMode::Hardcore,
            'order_column' => 4,
            'awarded_at' => '2024-12-15 12:00:00',
        ]);

        // Act
        $response = $this->jsonApi('v2')
            ->expects('user-awards')
            ->withHeader('X-API-Key', 'test-key')
            ->get("/api/v2/users/{$player->ulid}/awards?filter[awardedFrom]=2024-03-01&filter[awardedTo]=2024-09-01");

        // Assert
        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertCount(2, $data);
        $ids = array_column($data, 'id');
        $this->assertContains((string) $inRangeAward->id, $ids);
        $this->assertContains((string) $lateFinalDayAward->id, $ids); // a bare-date awardedTo bound is treated as end-of-day
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
            'award_tier' => UnlockMode::Casual,
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

    public function testItCollapsesMediaContributionAwardsToTheHighestTier(): void
    {
        // Arrange
        User::factory()->create(['web_api_key' => 'test-key']);
        $player = User::factory()->create();

        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::MediaContribution,
            'award_key' => 1,
            'award_tier' => 1,
            'order_column' => 0,
        ]);
        PlayerBadge::factory()->create([
            'user_id' => $player->id,
            'award_type' => AwardType::MediaContribution,
            'award_key' => 2,
            'award_tier' => 2,
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
        $this->assertEquals('media-contribution', $response->json('data.0.attributes.kind'));
        $this->assertEquals(2, $response->json('data.0.attributes.context.tier'));
        $this->assertEquals(1, $response->json('meta.totalAwardsCount'));
        $this->assertEquals(1, $response->json('meta.siteAwardsCount'));
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
