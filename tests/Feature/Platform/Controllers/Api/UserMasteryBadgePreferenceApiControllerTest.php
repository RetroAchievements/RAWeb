<?php

declare(strict_types=1);

use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\GameBadge;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(LazilyRefreshDatabase::class);

/**
 * @return array{0: Game, 1: GameBadge, 2: GameBadge} [game, currentBadge, historicalBadge]
 */
function createMasteredGameWithBadges(User $user): array
{
    $system = System::factory()->create();
    $game = Game::factory()->create([
        'system_id' => $system->id,
        'image_icon_asset_path' => '/Images/100001.png',
    ]);

    $current = GameBadge::factory()->create([
        'game_id' => $game->id,
        'image_asset_path' => '/Images/100001.png',
        'sha1' => str_repeat('a', 40),
        'became_current_at' => now()->subDay(),
        'replaced_at' => null,
    ]);

    $historical = GameBadge::factory()->create([
        'game_id' => $game->id,
        'image_asset_path' => '/Images/100000.png',
        'sha1' => str_repeat('b', 40),
        'became_current_at' => now()->subDays(3),
        'replaced_at' => now()->subDay(),
    ]);

    PlayerBadge::factory()->create([
        'user_id' => $user->id,
        'award_type' => AwardType::Mastery,
        'award_key' => $game->id,
        'award_tier' => 1,
    ]);

    return [$game, $current, $historical];
}

describe('Updating a badge preference', function () {
    it('given an unauthenticated request, returns a 401', function () {
        // ACT
        $response = postJson(route('api.user.mastery-badge-preference.update'), [
            'gameId' => 1,
            'sha1' => str_repeat('b', 40),
        ]);

        // ASSERT
        $response->assertStatus(401);
    });

    it('given a user who has not mastered the game, returns a 403', function () {
        // ARRANGE
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        GameBadge::factory()->create(['game_id' => $game->id, 'sha1' => str_repeat('b', 40), 'replaced_at' => now()]);

        // ACT
        $response = actingAs($user)->postJson(route('api.user.mastery-badge-preference.update'), [
            'gameId' => $game->id,
            'sha1' => str_repeat('b', 40),
        ]);

        // ASSERT
        $response->assertStatus(403);
    });

    it('given a chosen historical badge, stores the preference', function () {
        // ARRANGE
        $user = User::factory()->create();
        [$game, , $historical] = createMasteredGameWithBadges($user);

        // ACT
        $response = actingAs($user)->postJson(route('api.user.mastery-badge-preference.update'), [
            'gameId' => $game->id,
            'sha1' => $historical->sha1,
        ]);

        // ASSERT
        $response->assertOk()->assertJson(['success' => true, 'url' => media_asset($historical->image_asset_path)]);
        expect($user->badgePreferences()->where('game_id', $game->id)->value('sha1'))->toEqual($historical->sha1);
    });

    it('given the canonical badge, clears the preference', function () {
        // ARRANGE
        $user = User::factory()->create();
        [$game, $current, $historical] = createMasteredGameWithBadges($user);
        $user->badgePreferences()->create(['game_id' => $game->id, 'sha1' => $historical->sha1]);

        // ACT
        $response = actingAs($user)->postJson(route('api.user.mastery-badge-preference.update'), [
            'gameId' => $game->id,
            'sha1' => $current->sha1,
        ]);

        // ASSERT
        $response->assertOk();
        expect($user->badgePreferences()->where('game_id', $game->id)->exists())->toBeFalse();
    });

    it('given no sha1, clears the preference', function () {
        // ARRANGE
        $user = User::factory()->create();
        [$game, , $historical] = createMasteredGameWithBadges($user);
        $user->badgePreferences()->create(['game_id' => $game->id, 'sha1' => $historical->sha1]);

        // ACT
        $response = actingAs($user)->postJson(route('api.user.mastery-badge-preference.update'), ['gameId' => $game->id]);

        // ASSERT
        $response->assertOk();
        expect($user->badgePreferences()->where('game_id', $game->id)->exists())->toBeFalse();
    });

    it('given a sha1 that is not a selectable badge of the game, returns a 422', function () {
        // ARRANGE
        $user = User::factory()->create();
        [$game, , $historical] = createMasteredGameWithBadges($user);

        // ASSERT
        actingAs($user)->postJson(route('api.user.mastery-badge-preference.update'), [
            'gameId' => $game->id,
            'sha1' => str_repeat('c', 40),
        ])->assertStatus(422);

        $historical->delete();
        actingAs($user)->postJson(route('api.user.mastery-badge-preference.update'), [
            'gameId' => $game->id,
            'sha1' => $historical->sha1,
        ])->assertStatus(422);
    });
});

describe('Listing selectable badges', function () {
    it('given a user who has not mastered the game, returns a 403', function () {
        // ARRANGE
        $user = User::factory()->create();
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        // ACT
        $response = actingAs($user)->getJson(route('api.user.mastery-badge-preference.index', ['game' => $game->id]));

        // ASSERT
        $response->assertStatus(403);
    });

    it('given a mastered game, returns the selectable badges and marks the current and selected', function () {
        // ARRANGE
        $user = User::factory()->create();
        [$game, $current, $historical] = createMasteredGameWithBadges($user);
        GameBadge::factory()->create(['game_id' => $game->id, 'sha1' => str_repeat('d', 40), 'replaced_at' => now()])->delete();
        $user->badgePreferences()->create(['game_id' => $game->id, 'sha1' => $historical->sha1]);

        // ACT
        $response = actingAs($user)->getJson(route('api.user.mastery-badge-preference.index', ['game' => $game->id]));

        // ASSERT
        $response->assertOk();
        $badges = collect($response->json('badges'));
        expect($badges)->toHaveCount(2);
        expect($badges->pluck('sha1')->all())->toEqualCanonicalizing([$current->sha1, $historical->sha1]);
        expect($badges->firstWhere('sha1', $current->sha1)['isCurrent'])->toBeTrue();
        expect($badges->firstWhere('sha1', $historical->sha1)['isCurrent'])->toBeFalse();
        expect($badges->firstWhere('sha1', $historical->sha1)['isSelected'])->toBeTrue();
        expect($badges->firstWhere('sha1', $current->sha1)['isSelected'])->toBeFalse();
    });
});
