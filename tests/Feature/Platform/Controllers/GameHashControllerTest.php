<?php

declare(strict_types=1);

use App\Enums\GameHashCompatibility;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementSetType;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

/**
 * Creates a game with achievements and optionally sets up its core achievement set.
 */
function createGameWithAchievementsForHashes(
    System $system,
    string $title,
    int $publishedCount = 6,
    bool $withCoreSet = true,
): Game {
    $developer = User::factory()->create();

    $game = Game::factory()->create(['title' => $title, 'system_id' => $system->id]);
    Achievement::factory()->promoted()->count($publishedCount)->create([
        'game_id' => $game->id,
        'user_id' => $developer->id,
    ]);

    if ($withCoreSet && $publishedCount > 0) {
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);
    }

    return $game;
}

/**
 * Creates a base game with a linked subset.
 *
 * @return array{baseGame: Game, subsetGame: Game, subsetSet: GameAchievementSet}
 */
function createGameWithSubsetForHashes(
    System $system,
    string $baseTitle,
    string $subsetTitle,
    AchievementSetType $subsetType = AchievementSetType::Bonus,
): array {
    $baseGame = createGameWithAchievementsForHashes($system, $baseTitle, 10);
    $subsetGame = createGameWithAchievementsForHashes($system, $subsetTitle, 6);

    (new AssociateAchievementSetToGameAction())->execute(
        $baseGame,
        $subsetGame,
        $subsetType,
        $subsetType->label()
    );

    $subsetSet = GameAchievementSet::where('game_id', $baseGame->id)
        ->where('type', $subsetType)
        ->first();

    return [
        'baseGame' => $baseGame,
        'subsetGame' => $subsetGame,
        'subsetSet' => $subsetSet,
    ];
}

describe('Redirects', function () {
    it('given an invalid set id, redirects to the page without a set param', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievementsForHashes($system, 'Test Game');

        // ACT
        $response = get(route('game.hashes.index', ['game' => $game, 'set' => 99999]));

        // ASSERT
        $response->assertRedirect(route('game.hashes.index', ['game' => $game]));
    });

    it('given a subset game, redirects to the base game with the set param', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame] = createGameWithSubsetForHashes(
            $system,
            'The Legend of Zelda',
            'The Legend of Zelda [Subset - Low%]',
            AchievementSetType::Specialty,
        );

        $subsetCoreSet = GameAchievementSet::where('game_id', $subsetGame->id)
            ->where('type', AchievementSetType::Core)
            ->first();

        // ACT
        $response = get(route('game.hashes.index', ['game' => $subsetGame]));

        // ASSERT
        $response->assertRedirect(route('game.hashes.index', [
            'game' => $baseGame->id,
            'set' => $subsetCoreSet->achievement_set_id,
        ]));
    });

    it('given a subset game with a set param, does not redirect', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame, 'subsetSet' => $subsetSet] = createGameWithSubsetForHashes(
            $system,
            'The Legend of Zelda',
            'The Legend of Zelda [Subset - Low%]',
            AchievementSetType::Specialty,
        );

        // ACT
        $response = get(route('game.hashes.index', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id,
        ]));

        // ASSERT
        $response->assertOk();
    });

});

describe('Basic Rendering', function () {
    it('given the user is unauthenticated, still renders the page', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievementsForHashes($system, 'Test Game');
        GameHash::factory()->count(2)->create(['game_id' => $game->id]);

        // ACT
        $response = get(route('game.hashes.index', ['game' => $game]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('game/[game]/hashes'));
    });

    it('given the user is authenticated, still renders the page', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievementsForHashes($system, 'Test Game');
        GameHash::factory()->count(2)->create(['game_id' => $game->id]);
        $user = User::factory()->create();

        // ACT
        actingAs($user);
        $response = get(route('game.hashes.index', ['game' => $game]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('game/[game]/hashes'));
    });
});

describe('Hash Props', function () {
    it('returns compatible hashes for non-multiset games', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievementsForHashes($system, 'Test Game');
        GameHash::factory()->count(3)->create([
            'game_id' => $game->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);

        // ACT
        $response = get(route('game.hashes.index', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hashes', 3)
        );
    });

    it('given a multiset game, the "core" view includes main and bonus hashes', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame] = createGameWithSubsetForHashes(
            $system,
            'Dragon Quest III',
            'Dragon Quest III [Subset - Bonus]',
            AchievementSetType::Bonus,
        );

        GameHash::factory()->count(3)->create([
            'game_id' => $baseGame->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        GameHash::factory()->count(2)->create([
            'game_id' => $subsetGame->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);

        // ACT
        $response = get(route('game.hashes.index', ['game' => $baseGame]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hashes', 5)
        );
    });

    it('given a bonus set linked to multiple parents, excludes bonus backing hashes from the parent game views', function () {
        // ARRANGE
        $system = System::factory()->create();

        // ... create two parent games with their own hashes ...
        $parentGameA = createGameWithAchievementsForHashes($system, 'Pokemon Red', 10);
        $parentGameB = createGameWithAchievementsForHashes($system, 'Pokemon Blue', 10);

        GameHash::factory()->count(3)->create([
            'game_id' => $parentGameA->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        GameHash::factory()->count(2)->create([
            'game_id' => $parentGameB->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);

        // ... create the shared bonus backing game with its own hashes ...
        $bonusBackingGame = createGameWithAchievementsForHashes($system, 'Pokemon Red | Pokemon Blue [Subset - Bonus]', 5);
        GameHash::factory()->count(4)->create([
            'game_id' => $bonusBackingGame->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);

        // ... link the bonus set to both parent games ...
        (new AssociateAchievementSetToGameAction())->execute(
            $parentGameA,
            $bonusBackingGame,
            AchievementSetType::Bonus,
            'Bonus'
        );
        (new AssociateAchievementSetToGameAction())->execute(
            $parentGameB,
            $bonusBackingGame,
            AchievementSetType::Bonus,
            'Bonus'
        );

        // ACT
        $response = get(route('game.hashes.index', ['game' => $parentGameA]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hashes', 3) // only includes Red's 3 hashes, none of the bonus backing game hashes
        );
    });

    it('given viewing a specialty subset, only includes specialty hashes', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame, 'subsetSet' => $subsetSet] = createGameWithSubsetForHashes(
            $system,
            'Final Fantasy VI',
            'Final Fantasy VI [Subset - Specialty]',
            AchievementSetType::Specialty,
        );

        GameHash::factory()->count(4)->create([
            'game_id' => $baseGame->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        GameHash::factory()->count(2)->create([
            'game_id' => $subsetGame->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);

        // ACT
        $response = get(route('game.hashes.index', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id, // !!
        ]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hashes', 2)
        );
    });

    it('returns incompatible hashes separately', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievementsForHashes($system, 'Test Game');
        GameHash::factory()->count(2)->create([
            'game_id' => $game->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        GameHash::factory()->count(3)->create([
            'game_id' => $game->id,
            'compatibility' => GameHashCompatibility::Incompatible,
        ]);

        // ACT
        $response = get(route('game.hashes.index', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hashes', 2)
            ->has('incompatibleHashes', 3)
        );
    });

    it('returns untested hashes separately', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievementsForHashes($system, 'Test Game');
        GameHash::factory()->count(2)->create([
            'game_id' => $game->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        GameHash::factory()->count(4)->create([
            'game_id' => $game->id,
            'compatibility' => GameHashCompatibility::Untested,
        ]);

        // ACT
        $response = get(route('game.hashes.index', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hashes', 2)
            ->has('untestedHashes', 4)
        );
    });

    it('returns patch required hashes separately', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievementsForHashes($system, 'Test Game');
        GameHash::factory()->count(2)->create([
            'game_id' => $game->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        GameHash::factory()->count(1)->create([
            'game_id' => $game->id,
            'compatibility' => GameHashCompatibility::PatchRequired,
        ]);

        // ACT
        $response = get(route('game.hashes.index', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hashes', 2)
            ->has('patchRequiredHashes', 1)
        );
    });
});

describe('Permissions Props', function () {
    it('given the user is a guest, manageGameHashes is false', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievementsForHashes($system, 'Test Game');

        // ACT
        $response = get(route('game.hashes.index', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('can.manageGameHashes', false)
        );
    });

    it('given the user is a developer, manageGameHashes is true', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);
        $system = System::factory()->create();
        $game = createGameWithAchievementsForHashes($system, 'Test Game');
        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        actingAs($developer);
        $response = get(route('game.hashes.index', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('can.manageGameHashes', true)
        );
    });
});
