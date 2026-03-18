<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

/**
 * Creates a subset game linked to a base game via AssociateAchievementSetToGameAction.
 */
function createSubsetAchievement(System $system, array $achievementOverrides = []): Achievement
{
    $baseGame = Game::factory()->create(['system_id' => $system->id]);
    $subsetGame = Game::factory()->create(['system_id' => $system->id]);

    Achievement::factory()->promoted()->count(3)->create([
        'game_id' => $baseGame->id,
        'user_id' => User::factory()->create()->id,
    ]);
    (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($baseGame);

    $achievement = Achievement::factory()->promoted()->create(array_merge([
        'game_id' => $subsetGame->id,
        'user_id' => User::factory()->create()->id,
    ], $achievementOverrides));
    (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($subsetGame);

    (new AssociateAchievementSetToGameAction())->execute(
        $baseGame,
        $subsetGame,
        AchievementSetType::Bonus,
        'Bonus'
    );

    return $achievement;
}

describe('Authorization', function () {
    it('given the user is unauthenticated, returns a 401', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
        ]);

        // ACT
        $response = patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'title' => 'New Title',
        ]);

        // ASSERT
        $response->assertUnauthorized();
    });

    it('given the user has no developer role, returns a 403', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
        ]);
        $user = User::factory()->create();

        // ACT
        $response = actingAs($user)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'title' => 'New Title',
        ]);

        // ASSERT
        $response->assertForbidden();
    });

    it('given the user is a junior developer without an active claim, returns a 403', function (bool $isPromoted) {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
            'is_promoted' => $isPromoted,
        ]);

        $juniorDeveloper = User::factory()->create();
        $juniorDeveloper->assignRole(Role::DEVELOPER_JUNIOR);

        // ACT
        $response = actingAs($juniorDeveloper)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'title' => 'New Title',
        ]);

        // ASSERT
        $response->assertForbidden();
    })->with([
        'promoted achievement' => [true],
        'unpromoted achievement' => [false],
    ]);
});

describe('Validation', function () {
    it('given invalid field data, returns a 422', function (array $payload) {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), $payload);

        // ASSERT
        $response->assertUnprocessable();
    })->with([
        'empty title' => [['title' => '']],
        'invalid points' => [['points' => 7]],
        'description too long' => [['description' => str_repeat('a', 256)]],
        'invalid type' => [['type' => 'invalid_type']],
    ]);
});

describe('Successful Updates', function () {
    it('given the user is a developer, they can update a single field', function (string $field, mixed $oldValue, mixed $newValue, string $modelAttribute) {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
            $modelAttribute => $oldValue,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            $field => $newValue,
        ]);

        // ASSERT
        $response->assertOk();
        $response->assertJson(['success' => true]);

        $achievement->refresh();
        expect($achievement->{$modelAttribute})->toEqual($newValue);
    })->with([
        'title' => ['title', 'Old Title', 'New Title', 'title'],
        'description' => ['description', 'Old description', 'New description', 'description'],
        'points' => ['points', 5, 25, 'points'],
        'type' => ['type', null, 'missable', 'type'],
    ]);

    it('given the user is a developer, they can set the type to null', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
            'type' => 'missable',
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'type' => null,
        ]);

        // ASSERT
        $response->assertOk();

        $achievement->refresh();
        expect($achievement->type)->toBeNull();
    });

    it('given the user is a developer, they can promote any achievement', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
            'is_promoted' => false,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'isPromoted' => true,
        ]);

        // ASSERT
        $response->assertOk();

        $achievement->refresh();
        expect($achievement->is_promoted)->toBeTrue();
    });

    it('given the user is a developer, they can demote any achievement', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'isPromoted' => false,
        ]);

        // ASSERT
        $response->assertOk();

        $achievement->refresh();
        expect($achievement->is_promoted)->toBeFalse();
    });

    it('given the user is a developer, they can update multiple fields at once', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
            'title' => 'Old Title',
            'description' => 'Old description',
            'points' => 5,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'title' => 'New Title',
            'description' => 'New description',
            'points' => 50,
        ]);

        // ASSERT
        $response->assertOk();

        $achievement->refresh();
        expect($achievement->title)->toEqual('New Title');
        expect($achievement->description)->toEqual('New description');
        expect($achievement->points)->toEqual(50);
    });
});

describe('Per-Field Authorization', function () {
    it('given the user is a writer, they can only update the title and description', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
            'title' => 'Old Title',
            'points' => 5,
        ]);

        $writer = User::factory()->create();
        $writer->assignRole(Role::WRITER);

        // ACT
        $response = actingAs($writer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'title' => 'New Title',
            'description' => 'New Description',
            'points' => 25, // !! writer cannot change points
        ]);

        // ASSERT
        $response->assertOk();

        $achievement->refresh();
        expect($achievement->title)->toEqual('New Title');
        expect($achievement->description)->toEqual('New Description');
        expect($achievement->points)->toEqual(5); // unchanged, silently skipped
    });

    it('given the user is a junior developer with an active claim on an unpromoted achievement, they can update allowed fields', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
            'title' => 'Old Title',
            'is_promoted' => false,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $juniorDeveloper = User::factory()->create();
        $juniorDeveloper->assignRole(Role::DEVELOPER_JUNIOR);

        AchievementSetClaim::factory()->create([
            'user_id' => $juniorDeveloper->id,
            'game_id' => $game->id,
        ]);

        // ACT
        $response = actingAs($juniorDeveloper)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'title' => 'Jr Dev Title',
        ]);

        // ASSERT
        $response->assertOk();

        $achievement->refresh();
        expect($achievement->title)->toEqual('Jr Dev Title');
    });

    it('given the user is a junior developer, silently skips the is_promoted field', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
            'is_promoted' => false,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $juniorDeveloper = User::factory()->create();
        $juniorDeveloper->assignRole(Role::DEVELOPER_JUNIOR);

        AchievementSetClaim::factory()->create([
            'user_id' => $juniorDeveloper->id,
            'game_id' => $game->id,
        ]);

        // ACT
        $response = actingAs($juniorDeveloper)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'isPromoted' => true, // junior devs cannot promote stuff themselves
        ]);

        // ASSERT
        $response->assertOk();

        $achievement->refresh();
        expect($achievement->is_promoted)->toBeFalse(); // the field value is unchanged!
    });
});

describe('Subset Type Restrictions', function () {
    it('given a subset achievement, rejects progression and win_condition types with a 422', function (string $type) {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $achievement = createSubsetAchievement($system);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'type' => $type,
        ]);

        // ASSERT
        $response->assertUnprocessable();
    })->with([
        'progression' => ['progression'],
        'win_condition' => ['win_condition'],
    ]);

    it('given a subset achievement, still allows missable type', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $achievement = createSubsetAchievement($system, ['type' => null]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'type' => 'missable',
        ]);

        // ASSERT
        $response->assertOk();

        $achievement->refresh();
        expect($achievement->type)->toEqual('missable');
    });

    it('given a non-subset achievement, allows progression and win condition types', function (string $type) {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => User::factory()->create()->id,
            'type' => null,
        ]);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->patchJson(route('api.achievement.update', ['achievement' => $achievement]), [
            'type' => $type,
        ]);

        // ASSERT
        $response->assertOk();

        $achievement->refresh();
        expect($achievement->type)->toEqual($type);
    })->with([
        'progression' => ['progression'],
        'win_condition' => ['win_condition'],
    ]);
});
