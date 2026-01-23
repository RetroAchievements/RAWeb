<?php

declare(strict_types=1);

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketState;
use App\Community\Enums\UserGameListType;
use App\Enums\GameHashCompatibility;
use App\Models\Achievement;
use App\Models\AchievementAuthor;
use App\Models\AchievementGroup;
use App\Models\AchievementMaintainer;
use App\Models\AchievementSetAchievement;
use App\Models\AchievementSetAuthor;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\GameSet;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\UnrankedUser;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Actions\AssociateAchievementSetToGameAction;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementSetAuthorTask;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\GameSetRolePermission;
use App\Platform\Enums\GameSetType;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Services\EventHubIdCacheService;
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
function createGameWithAchievements(
    System $system,
    string $title,
    int $publishedCount = 6,
    int $unpublishedCount = 0,
    bool $withCoreSet = true,
): Game {
    $developer = User::factory()->create();

    $game = Game::factory()->create(['title' => $title, 'system_id' => $system->id]);
    Achievement::factory()->promoted()->count($publishedCount)->create([
        'game_id' => $game->id,
        'user_id' => $developer->id,
    ]);
    Achievement::factory()->count($unpublishedCount)->create([
        'game_id' => $game->id,
        'user_id' => $developer->id,
    ]);

    if ($withCoreSet && ($publishedCount > 0 || $unpublishedCount > 0)) {
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);
    }

    return $game;
}

/**
 * Creates a base game with a linked subset.
 *
 * @return array{baseGame: Game, subsetGame: Game, subsetSet: GameAchievementSet}
 */
function createGameWithSubset(
    System $system,
    string $baseTitle,
    string $subsetTitle,
    AchievementSetType $subsetType = AchievementSetType::Bonus,
): array {
    $baseGame = createGameWithAchievements($system, $baseTitle, 10);
    $subsetGame = createGameWithAchievements($system, $subsetTitle, 6);

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
    it('given the game is a legacy "hub game", redirects to hub.show', function () {
        // ARRANGE
        $hubGame = Game::factory()->create(['system_id' => System::Hubs, 'title' => '[Central - Test Hub]']);
        $gameSet = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'game_id' => $hubGame->id,
            'title' => '[Central - Test Hub]',
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $hubGame]));

        // ASSERT
        $response->assertRedirect(route('hub.show', ['gameSet' => $gameSet]));
    });

    it('given the game is a hub with no GameSet entity, returns a 404', function () {
        // ARRANGE
        $hubGame = Game::factory()->create(['system_id' => System::Hubs, 'title' => 'Hub Game Without Set']);

        // ACT
        $response = get(route('game.show', ['game' => $hubGame]));

        // ASSERT
        $response->assertNotFound();
    });

    it('given the game is an "event game", redirects to event.show', function () {
        // ARRANGE
        $eventGame = Game::factory()->create(['system_id' => System::Events, 'title' => 'Event Game']);
        $event = Event::factory()->create(['legacy_game_id' => $eventGame->id]);

        // ACT
        $response = get(route('game.show', ['game' => $eventGame]));

        // ASSERT
        $response->assertRedirect(route('event.show', ['event' => $event]));
    });

    it('given a legacy Unofficial flag parameter, redirects using the new query param format', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game', 6, 2);

        // ACT
        $response = get(route('game.show', ['game' => $game, 'f' => '5']));

        // ASSERT
        $response->assertRedirect(route('game.show', ['game' => $game, 'unpublished' => 'true']));
    });

    it('given the game is a "subset game", redirects to the base game with a "set" query param', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame] = createGameWithSubset(
            $system,
            'Dragon Quest III',
            'Dragon Quest III [Subset - Bonus]'
        );

        $subsetCoreSet = GameAchievementSet::where('game_id', $subsetGame->id)
            ->where('type', AchievementSetType::Core)
            ->first();

        // ACT
        $response = get(route('game.show', ['game' => $subsetGame]));

        // ASSERT
        $response->assertRedirect(route('game.show', [
            'game' => $baseGame,
            'set' => $subsetCoreSet->achievement_set_id,
        ]));
    });

    it('given the game is a "subset game" linked to multiple parents, redirects to the first parent by creation date', function () {
        // ARRANGE
        $system = System::factory()->create();

        $subsetGame = createGameWithAchievements($system, 'Dragon Quest III [Subset - Bonus]', 6);
        $firstBaseGame = createGameWithAchievements($system, 'Dragon Quest III', 10);
        $secondBaseGame = createGameWithAchievements($system, 'Dragon Quest III (Japan)', 10);

        (new AssociateAchievementSetToGameAction())->execute(
            $firstBaseGame,
            $subsetGame,
            AchievementSetType::Bonus,
            'Bonus'
        );
        (new AssociateAchievementSetToGameAction())->execute(
            $secondBaseGame,
            $subsetGame,
            AchievementSetType::Bonus,
            'Bonus'
        );

        $subsetCoreSet = GameAchievementSet::where('game_id', $subsetGame->id)
            ->where('type', AchievementSetType::Core)
            ->first();

        // ACT
        $response = get(route('game.show', ['game' => $subsetGame]));

        // ASSERT
        $response->assertRedirect(route('game.show', [
            'game' => $firstBaseGame,
            'set' => $subsetCoreSet->achievement_set_id,
        ]));
    });

    it('given a set query param is already provided, does not redirect', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $coreSet = GameAchievementSet::where('game_id', $game->id)
            ->where('type', AchievementSetType::Core)
            ->first();

        // ACT
        $response = get(route('game.show', ['game' => $game, 'set' => $coreSet->achievement_set_id]));

        // ASSERT
        $response->assertOk();
    });

    it('given an invalid set id, redirects to the base game without a set param', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game, 'set' => '999999']));

        // ASSERT
        $response->assertRedirect(route('game.show', ['game' => $game]));
    });
});

describe('Basic Rendering', function () {
    it('given the user is unauthenticated, always renders the page', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Super Mario Bros.');

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('game/[game]')
            ->has('game')
            ->where('game.id', $game->id)
            ->where('game.title', 'Super Mario Bros.')
        );
    });

    it('given the user is authenticated, always renders the page', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Super Mario Bros.');
        $user = User::factory()->create();

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('game/[game]')
            ->has('game')
            ->where('game.id', $game->id)
        );
    });

    it('given a view query param, sets the initial view', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game, 'view' => 'leaderboards']));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('initialView', 'leaderboards')
        );
    });

    it('given a sort query param, sets the initial sort', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game, 'sort' => '-title']));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('initialSort', '-title')
        );
    });

    it('given unpublished assets are requested, properly sets the associated prop', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game', 5, 3);

        // ACT
        $response = get(route('game.show', ['game' => $game, 'unpublished' => 'true']));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isViewingPublishedAchievements', false)
        );
    });
});

describe('Core Props', function () {
    it('includes game and backing game data in props', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Super Mario Bros.');
        $game->refresh();

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('game')
            ->where('game.id', $game->id)
            ->where('game.title', 'Super Mario Bros.')
            ->has('game.badgeUrl')
            ->has('game.system')
            ->where('game.system.id', $game->system->id)
            ->where('game.achievementsPublished', 6)
            ->has('backingGame')
            ->where('backingGame.id', $game->id)
            ->where('backingGame.achievementsPublished', 6)
            ->has('backingGame.badgeUrl')
            ->has('banner')
        );
    });

    it('given a subset target, backing game differs from game', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetSet' => $subsetSet] = createGameWithSubset(
            $system,
            'Dragon Quest III',
            'Dragon Quest III [Subset - Bonus]'
        );

        // ACT
        $response = get(route('game.show', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id,
        ]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('game.id', $baseGame->id)
            ->has('backingGame')
        );
    });
});

describe('User State Props', function () {
    it('given the game is on the Want to Play list, includes that state', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        UserGameListEntry::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'type' => UserGameListType::Play,
        ]);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isOnWantToPlayList', true)
        );
    });

    it('given the game is on the Want to Dev list, includes that state', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        UserGameListEntry::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'type' => UserGameListType::Develop,
        ]);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isOnWantToDevList', true)
        );
    });

    it('given the user is an unauthenticated guest, the list state props are always false', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isOnWantToPlayList', false)
            ->where('isOnWantToDevList', false)
        );
    });
});

describe('Claims Props', function () {
    it('given there is an active claim, includes it in props', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        AchievementSetClaim::factory()->create([
            'user_id' => $developer->id,
            'game_id' => $game->id,
            'claim_type' => ClaimType::Primary,
            'status' => ClaimStatus::Active,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('achievementSetClaims', 1)
            ->where('achievementSetClaims.0.user.displayName', $developer->display_name)
            ->where('achievementSetClaims.0.status', ClaimStatus::Active)
        );
    });

    it('given there is an in-review claim, includes it in props', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        AchievementSetClaim::factory()->create([
            'user_id' => $developer->id,
            'game_id' => $game->id,
            'claim_type' => ClaimType::Primary,
            'status' => ClaimStatus::InReview,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('achievementSetClaims', 1)
            ->where('achievementSetClaims.0.status', ClaimStatus::InReview)
        );
    });

    it('given there is a completed claim, excludes it from props', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        AchievementSetClaim::factory()->create([
            'user_id' => $developer->id,
            'game_id' => $game->id,
            'claim_type' => ClaimType::Primary,
            'status' => ClaimStatus::Complete,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('achievementSetClaims', 0)
        );
    });
});

describe('Permissions Props', function () {
    it('given the user is a guest, sets very limited permissions', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.createAchievementSetClaims', false)
            ->where('can.createGameComments', false)
            ->where('can.createGameForumTopic', false)
            ->where('can.manageAchievementSetClaims', false)
            ->where('can.manageGameHashes', false)
            ->where('can.manageGames', false)
            ->where('can.reviewAchievementSetClaims', false)
            ->where('can.updateAnyAchievementSetClaim', false)
            ->where('can.updateGame', false)
            ->where('can.viewDeveloperInterest', false)
        );
    });

    it('given the user is authenticated and has no special roles, allows commenting but no dev-related stuff', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create(['points_hardcore' => 100]);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.createAchievementSetClaims', false)
            ->where('can.createGameComments', true)
            ->where('can.createGameForumTopic', false)
            ->where('can.manageAchievementSetClaims', false)
            ->where('can.manageGameHashes', false)
            ->where('can.manageGames', false)
            ->where('can.reviewAchievementSetClaims', false)
            ->where('can.updateAnyAchievementSetClaim', false)
            ->where('can.updateGame', false)
            ->where('can.viewDeveloperInterest', false)
        );
    });

    it('given the user is a Junior Developer, allows limited development permissions', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $game->update(['forum_topic_id' => 12345]); // jr devs can't create claims without a forum topic

        $juniorDeveloper = User::factory()->create(['points_hardcore' => 100]);
        $juniorDeveloper->assignRole(Role::DEVELOPER_JUNIOR);

        // ACT
        $response = actingAs($juniorDeveloper)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.createAchievementSetClaims', true)
            ->where('can.createGameComments', true)
            ->where('can.createGameForumTopic', false) // jr devs cannot create forum topics
            ->where('can.manageAchievementSetClaims', true)
            ->where('can.manageGameHashes', false) // jr devs cannot manage hashes
            ->where('can.manageGames', true)
            ->where('can.reviewAchievementSetClaims', false)
            ->where('can.updateAnyAchievementSetClaim', false)
            ->where('can.updateGame', false) // requires a claim
            ->where('can.viewDeveloperInterest', false) // requires a claim
        );
    });

    it('given the user is a developer, includes elevated permissions', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.createAchievementSetClaims', true)
            ->where('can.createGameComments', true)
            ->where('can.createGameForumTopic', true) // full devs can create official forum topics
            ->where('can.manageAchievementSetClaims', true)
            ->where('can.manageGameHashes', true)
            ->where('can.manageGames', true)
            ->where('can.reviewAchievementSetClaims', false) // requires CODE_REVIEWER
            ->where('can.updateAnyAchievementSetClaim', false) // requires ADMINISTRATOR/MODERATOR
            ->where('can.updateGame', true)
            ->where('can.viewDeveloperInterest', false) // requires an active claim, which this user doesn't have
        );
    });

    it('given the user is a developer with an active claim, they can view developer interest', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        AchievementSetClaim::factory()->create([
            'user_id' => $developer->id,
            'game_id' => $game->id,
            'claim_type' => ClaimType::Primary,
            'status' => ClaimStatus::Active,
        ]);

        // ACT
        $response = actingAs($developer)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.viewDeveloperInterest', true)
        );
    });

    it('given the user is a junior developer with an active claim, they can update game and view developer interest', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $game->update(['forum_topic_id' => 12345]);

        $juniorDeveloper = User::factory()->create();
        $juniorDeveloper->assignRole(Role::DEVELOPER_JUNIOR);

        AchievementSetClaim::factory()->create([
            'user_id' => $juniorDeveloper->id,
            'game_id' => $game->id,
            'claim_type' => ClaimType::Primary,
            'status' => ClaimStatus::Active,
        ]);

        // ACT
        $response = actingAs($juniorDeveloper)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.updateGame', true)
            ->where('can.viewDeveloperInterest', true)
        );
    });

    it('given the user is a code reviewer and some junior developer has an active claim, the CR can review the claim', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $game->update(['forum_topic_id' => 12345]);

        $codeReviewer = User::factory()->create();
        $codeReviewer->assignRole(Role::DEVELOPER);
        $codeReviewer->assignRole(Role::CODE_REVIEWER);

        // ... a junior developer must have an active primary claim for the code reviewer to review ...
        $juniorDeveloper = User::factory()->create();
        $juniorDeveloper->assignRole(Role::DEVELOPER_JUNIOR);

        AchievementSetClaim::factory()->create([
            'user_id' => $juniorDeveloper->id,
            'game_id' => $game->id,
            'claim_type' => ClaimType::Primary,
            'status' => ClaimStatus::Active,
        ]);

        // ACT
        $response = actingAs($codeReviewer)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.reviewAchievementSetClaims', true)
        );
    });

    it('given the user is a moderator, they have moderation-specific permissions', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $game->update(['forum_topic_id' => 12345]);

        $moderator = User::factory()->create();
        $moderator->assignRole(Role::MODERATOR);

        $juniorDeveloper = User::factory()->create();
        $juniorDeveloper->assignRole(Role::DEVELOPER_JUNIOR);

        AchievementSetClaim::factory()->create([
            'user_id' => $juniorDeveloper->id,
            'game_id' => $game->id,
            'claim_type' => ClaimType::Primary,
            'status' => ClaimStatus::Active,
        ]);

        // ACT
        $response = actingAs($moderator)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.createAchievementSetClaims', false) // they're not a developer
            ->where('can.createGameComments', true)
            ->where('can.createGameForumTopic', false) // the game already has a forum topic
            ->where('can.manageAchievementSetClaims', true)
            ->where('can.manageGameHashes', false) // they're not a developer
            ->where('can.manageGames', false) // they're not a developer
            ->where('can.reviewAchievementSetClaims', true) // mods can toggle in-review status for jr dev claims
            ->where('can.updateAnyAchievementSetClaim', true) // mods can manage all claims
            ->where('can.updateGame', false) // they're not a developer
            ->where('can.viewDeveloperInterest', true) // mods can always see this page
        );
    });

    it('given the user is an administrator, has admin-specific permissions', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $administrator = User::factory()->create();
        $administrator->assignRole(Role::ADMINISTRATOR);

        // ACT
        $response = actingAs($administrator)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.createAchievementSetClaims', false)
            ->where('can.createGameComments', true)
            ->where('can.createGameForumTopic', false)
            ->where('can.manageAchievementSetClaims', false)
            ->where('can.manageGameHashes', false)
            ->where('can.manageGames', false)
            ->where('can.reviewAchievementSetClaims', false)
            ->where('can.updateAnyAchievementSetClaim', true)
            ->where('can.updateGame', false)
            ->where('can.viewDeveloperInterest', true)
        );
    });
});

describe('Count Props', function () {
    it('always includes various entity counts', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        Comment::factory()->count(3)->create([
            'commentable_id' => $game->id,
            'commentable_type' => CommentableType::Game,
            'user_id' => $user->id,
        ]);

        Leaderboard::factory()->count(2)->create([
            'game_id' => $game->id,
            'order_column' => 1,
        ]);

        GameHash::factory()->count(4)->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numComments', 3)
            ->where('numLeaderboards', 2)
            ->where('numCompatibleHashes', 4)
        );
    });

    it('given a multiset-enabled game, the core set view includes main, bonus, and specialty hashes', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame] = createGameWithSubset(
            $system,
            'Base Game',
            'Base Game [Subset - Bonus]',
            AchievementSetType::Bonus
        );

        GameHash::factory()->count(3)->create([
            'game_id' => $baseGame->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        GameHash::factory()->count(3)->create([
            'game_id' => $baseGame->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::Incompatible,
        ]);
        GameHash::factory()->count(2)->create([
            'game_id' => $subsetGame->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);

        $baseGame->refresh();

        // ACT
        $response = get(route('game.show', ['game' => $baseGame]));

        // ASSERT
        // ... main game hashes (3) + bonus hashes (2) ...
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numCompatibleHashes', 5)
        );
    });

    it('given viewing a specialty subset, only includes "backing game" hashes', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame, 'subsetSet' => $subsetSet] = createGameWithSubset(
            $system,
            'Base Game',
            'Base Game [Subset - Specialty]',
            AchievementSetType::Specialty
        );

        GameHash::factory()->count(4)->create([
            'game_id' => $baseGame->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);
        GameHash::factory()->count(2)->create([
            'game_id' => $subsetGame->id,
            'system_id' => $system->id,
            'compatibility' => GameHashCompatibility::Compatible,
        ]);

        // ACT
        $response = get(route('game.show', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id,
        ]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numCompatibleHashes', 2)
        );
    });
});

describe('Player Props', function () {
    it('given the user has progress, includes their PlayerGame record', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 3,
        ]);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('playerGame')
            ->where('playerGame.achievementsUnlocked', 3)
        );
    });

    it('given the user has no progress, playerGame is null', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        // ... no PlayerGame created here ...

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('playerGame')
        );
    });

    it('given the user is a guest, playerGame is null', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('playerGame')
        );
    });
});

describe('Achievement Set Props', function () {
    it('always includes all selectable achievement sets', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame] = createGameWithSubset(
            $system,
            'Dragon Quest III',
            'Dragon Quest III [Subset - Bonus]'
        );

        // ACT
        $response = get(route('game.show', ['game' => $baseGame]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('selectableGameAchievementSets', 2)
        );
    });

    it('given a set query param, includes the target achievement set id', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetSet' => $subsetSet] = createGameWithSubset(
            $system,
            'Dragon Quest III',
            'Dragon Quest III [Subset - Bonus]'
        );

        // ACT
        $response = get(route('game.show', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id,
        ]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('targetAchievementSetId', $subsetSet->achievement_set_id)
        );
    });

    it('given a non-core set, includes target set player counts', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetSet' => $subsetSet] = createGameWithSubset(
            $system,
            'Dragon Quest III',
            'Dragon Quest III [Subset - Bonus]'
        );

        // ACT
        $response = get(route('game.show', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id,
        ]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('targetAchievementSetPlayersTotal')
            ->has('targetAchievementSetPlayersHardcore')
        );
    });
});

describe('Leaderboard State Props', function () {
    it('given leaderboards with mixed states, by default shows active and disabled, counts only active, and excludes unpublished', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $activeLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Active,
            'order_column' => 2,
        ]);
        $disabledLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Disabled,
            'order_column' => 1,
        ]);
        $unpublishedLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Unpublished,
            'order_column' => 0,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game, 'view' => 'leaderboards']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            // ... only active leaderboards count toward numLeaderboards when viewing published ...
            ->where('numLeaderboards', 1)

            // ... both active and disabled are in the listing, but not unpublished ...
            ->has('allLeaderboards', 2)
            ->where('allLeaderboards.0.id', $activeLeaderboard->id)
            ->where('allLeaderboards.1.id', $disabledLeaderboard->id)
        );
    });

    it('given the unpublished query param, shows only unpublished leaderboards and counts them', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game', 5, 2);

        Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Active,
            'order_column' => 1,
        ]);
        Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Disabled,
            'order_column' => 2,
        ]);
        $unpublishedLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Unpublished,
            'order_column' => 0,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game, 'view' => 'leaderboards', 'unpublished' => 'true']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            // ... only unpublished leaderboards count when viewing unpublished ...
            ->where('numLeaderboards', 1)

            // ... only unpublished leaderboards are in the listing ...
            ->has('allLeaderboards', 1)
            ->where('allLeaderboards.0.id', $unpublishedLeaderboard->id)
        );
    });

    it('given featured leaderboards, only active leaderboards are included', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $activeLeaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Active,
            'order_column' => 1,
        ]);
        Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Disabled,
            'order_column' => 2,
        ]);
        Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Unpublished,
            'order_column' => 0,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('featuredLeaderboards', 1)
            ->where('featuredLeaderboards.0.id', $activeLeaderboard->id)
        );
    });

    it('each leaderboard includes its state in the response', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Disabled,
            'order_column' => 1,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game, 'view' => 'leaderboards']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('allLeaderboards', 1)
            ->where('allLeaderboards.0.state', 'disabled')
        );
    });

    it('given an inactive system, leaderboard count is zero', function () {
        // ARRANGE
        $system = System::factory()->create(['active' => false]);
        $game = createGameWithAchievements($system, 'Test Game');

        Leaderboard::factory()->count(3)->create([
            'game_id' => $game->id,
            'order_column' => 1,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numLeaderboards', 0)
        );
    });
});

describe('User Leaderboard Entry Props', function () {
    function createLeaderboardWithEntries(
        Game $game,
        array $entries,
        bool $rankAsc = false,
        int $orderColumn = 1,
    ): Leaderboard {
        $leaderboard = Leaderboard::factory()->create([
            'game_id' => $game->id,
            'state' => LeaderboardState::Active,
            'order_column' => $orderColumn,
            'rank_asc' => $rankAsc,
        ]);

        foreach ($entries as $entry) {
            LeaderboardEntry::factory()->create([
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $entry['user']->id,
                'score' => $entry['score'],
            ]);
        }

        return $leaderboard;
    }

    it('given the user has a leaderboard entry, includes their entry and rank', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        createLeaderboardWithEntries($game, [
            ['user' => $user, 'score' => 1000],
        ]);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game, 'view' => 'leaderboards']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('allLeaderboards', 1)
            ->has('allLeaderboards.0.userEntry')
            ->where('allLeaderboards.0.userEntry.rank', 1)
            ->where('allLeaderboards.0.userEntry.formattedScore', '1,000')
        );
    });

    it('given a leaderboard where rankAsc is false, calculates the user rank correctly based on higher scores being better', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        createLeaderboardWithEntries($game, [
            ['user' => User::factory()->create(), 'score' => 3000], // rank 1
            ['user' => User::factory()->create(), 'score' => 2000], // rank 2
            ['user' => $user, 'score' => 1000],                     // rank 3
        ], rankAsc: false);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game, 'view' => 'leaderboards']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('allLeaderboards.0.userEntry.rank', 3)
        );
    });

    it('given a leaderboard where rankAsc is true, calculates the user rank correctly based on lower scores being better', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        createLeaderboardWithEntries($game, [
            ['user' => User::factory()->create(), 'score' => 100], // rank 1
            ['user' => User::factory()->create(), 'score' => 200], // rank 2
            ['user' => $user, 'score' => 300],                     // rank 3
        ], rankAsc: true);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game, 'view' => 'leaderboards']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('allLeaderboards.0.userEntry.rank', 3)
        );
    });

    it('excludes unranked users from rank calculations', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        $unrankedUser = User::factory()->create(['unranked_at' => now()]);
        UnrankedUser::create(['user_id' => $unrankedUser->id]);

        createLeaderboardWithEntries($game, [
            ['user' => $unrankedUser, 'score' => 5000], // would be rank 1 if not unranked
            ['user' => $user, 'score' => 1000],
        ], rankAsc: false);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game, 'view' => 'leaderboards']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('allLeaderboards.0.userEntry.rank', 1)
        );
    });

    it('given multiple leaderboards, calculates ranks correctly for each', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        createLeaderboardWithEntries($game, [
            ['user' => $otherUser, 'score' => 2000],
            ['user' => $user, 'score' => 1000], // rank 2
        ], rankAsc: false, orderColumn: 1);

        createLeaderboardWithEntries($game, [
            ['user' => $otherUser, 'score' => 500],
            ['user' => $user, 'score' => 3000], // rank 1
        ], rankAsc: false, orderColumn: 2);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game, 'view' => 'leaderboards']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('allLeaderboards', 2)
            ->where('allLeaderboards.0.userEntry.rank', 2)
            ->where('allLeaderboards.1.userEntry.rank', 1)
        );
    });

    it('given a guest user, does not include user entries', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        createLeaderboardWithEntries($game, [
            ['user' => User::factory()->create(), 'score' => 1000],
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game, 'view' => 'leaderboards']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('allLeaderboards', 1)
            ->missing('allLeaderboards.0.userEntry')
        );
    });
});

describe('Related Content Props', function () {
    it('always includes similar games', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Super Mario Bros.');
        $similarGame = createGameWithAchievements($system, 'Super Mario Bros. 2');

        $similarGamesSet = GameSet::factory()->create([
            'type' => GameSetType::SimilarGames,
            'game_id' => $game->id,
        ]);
        $similarGamesSet->games()->attach([$similarGame->id]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('similarGames', 1)
            ->where('similarGames.0.id', $similarGame->id)
        );
    });

    it('always excludes "subset games" from similar games', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Super Mario Bros.');
        $similarGame = createGameWithAchievements($system, 'Super Mario Bros. 2');

        $subsetGame = Game::factory()->create([
            'title' => 'Super Mario Bros. [Subset - Bonus]',
            'system_id' => $system->id,
            'achievements_published' => 6,
        ]);

        $similarGamesSet = GameSet::factory()->create([
            'type' => GameSetType::SimilarGames,
            'game_id' => $game->id,
        ]);
        $similarGamesSet->games()->attach([$similarGame->id, $subsetGame->id]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('similarGames', 1)
            ->where('similarGames.0.id', $similarGame->id)
        );
    });

    it('always includes related hubs', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Super Mario Bros.');

        $hub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'title' => '[Series - Super Mario]',
        ]);
        $hub->games()->attach([$game->id]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hubs', 1)
            ->where('hubs.0.id', $hub->id)
        );
    });

    it('filters hubs by user permissions', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Super Mario Bros.');

        $publicHub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'title' => '[Series - Super Mario]',
        ]);
        $publicHub->games()->attach([$game->id]);

        $restrictedHub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'title' => '[Restricted Hub]',
        ]);
        $moderatorRole = Role::where('name', Role::MODERATOR)->first();
        $restrictedHub->viewRoles()->attach($moderatorRole, ['permission' => GameSetRolePermission::View->value]);
        $restrictedHub->games()->attach([$game->id]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hubs', 1)
            ->where('hubs.0.id', $publicHub->id)
        );
    });

    it('sorts similar games with achievements before similar games without achievements', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Main Game');

        $gameWithAchievements = createGameWithAchievements($system, 'AAA Game With Achievements', 10);
        $gameWithoutAchievements = Game::factory()->create([
            'title' => 'AAA Game Without Achievements',
            'system_id' => $system->id,
            'achievements_published' => 0,
        ]);

        $similarGamesSet = GameSet::factory()->create([
            'type' => GameSetType::SimilarGames,
            'game_id' => $game->id,
        ]);
        // ... attach the games in the wrong order to verify they sort correctly ...
        $similarGamesSet->games()->attach([$gameWithoutAchievements->id, $gameWithAchievements->id]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('similarGames', 2)
            ->where('similarGames.0.id', $gameWithAchievements->id)
            ->where('similarGames.1.id', $gameWithoutAchievements->id)
        );
    });
});

describe('Subscription Props', function () {
    it('given the user is authenticated, includes subscription states', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isSubscribedToComments', false)
            ->where('isSubscribedToAchievementComments', false)
            ->where('isSubscribedToTickets', false)
        );
    });

    it('given the user is a guest, subscription states are always false', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isSubscribedToComments', false)
            ->where('isSubscribedToAchievementComments', false)
            ->where('isSubscribedToTickets', false)
        );
    });
});

describe('Default Sort Props', function () {
    it('given a guest user, the default sort is always displayOrder', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('defaultSort', 'displayOrder')
        );
    });

    it('given the user has no progress, the default sort is always displayOrder', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('defaultSort', 'displayOrder')
        );
    });

    it('given the user has partial progress, the default sort is normal', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $user = User::factory()->create();

        // ... the user has unlocked some, but not all (!!), achievements ...
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 3,
        ]);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('defaultSort', 'normal')
        );
    });

    it('given the user has completed all achievements, the default sort is displayOrder', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game', publishedCount: 6); // !! 6
        $user = User::factory()->create();

        // ... the user has unlocked all achievements ...
        PlayerGame::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 6, // !! 6
        ]);

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('defaultSort', 'displayOrder')
        );
    });
});

describe('Open Tickets Props', function () {
    it('given viewing published achievements, counts published asset tickets', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $publishedAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        $unpublishedAchievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'is_promoted' => false,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $reporter = User::factory()->create();

        // ... create tickets for both achievements ...
        Ticket::factory()->create([
            'ticketable_id' => $publishedAchievement->id,
            'reporter_id' => $reporter->id,
            'ticketable_author_id' => $developer->id,
            'state' => TicketState::Open,
        ]);
        Ticket::factory()->create([
            'ticketable_id' => $unpublishedAchievement->id,
            'reporter_id' => $reporter->id,
            'ticketable_author_id' => $developer->id,
            'state' => TicketState::Open,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        // ... when viewing published achievements, only count tickets for published stuff ...
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numOpenTickets', 1)
        );
    });

    it('given viewing unpublished achievements, counts unpublished asset tickets', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $publishedAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        $unpublishedAchievement = Achievement::factory()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'is_promoted' => false,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $reporter = User::factory()->create();
        $reporter2 = User::factory()->create();

        // ... create tickets for both achievements ...
        Ticket::factory()->create([
            'ticketable_id' => $publishedAchievement->id,
            'reporter_id' => $reporter->id,
            'ticketable_author_id' => $developer->id,
            'state' => TicketState::Open,
        ]);
        Ticket::factory()->create([
            'ticketable_id' => $unpublishedAchievement->id,
            'reporter_id' => $reporter->id,
            'ticketable_author_id' => $developer->id,
            'state' => TicketState::Open,
        ]);
        Ticket::factory()->create([
            'ticketable_id' => $unpublishedAchievement->id,
            'reporter_id' => $reporter2->id, // Use different reporter to avoid unique constraint.
            'ticketable_author_id' => $developer->id,
            'state' => TicketState::Open,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game, 'unpublished' => 'true']));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numOpenTickets', 2)
        );
    });

    it('always excludes resolved tickets from the count', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $reporter = User::factory()->create();
        $reporter2 = User::factory()->create();

        // ... create an open ticket and a resolved ticket ...
        Ticket::factory()->create([
            'ticketable_id' => $achievement->id,
            'reporter_id' => $reporter->id,
            'ticketable_author_id' => $developer->id,
            'state' => TicketState::Open,
        ]);
        Ticket::factory()->create([
            'ticketable_id' => $achievement->id,
            'reporter_id' => $reporter2->id, // Use different reporter to avoid unique constraint.
            'ticketable_author_id' => $developer->id,
            'state' => TicketState::Resolved, // !!
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numOpenTickets', 1)
        );
    });
});

describe('Completion Stats Props', function () {
    it('includes num masters count', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $game->refresh();

        $masteredUser = User::factory()->create();
        PlayerGame::factory()->create([
            'user_id' => $masteredUser->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 6,
            'achievements_unlocked_hardcore' => 6,
            'points_hardcore' => 100,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numMasters', 1)
        );
    });

    it('includes num completions count for softcore only players', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $game->refresh();

        $softcoreUser = User::factory()->create();
        PlayerGame::factory()->create([
            'user_id' => $softcoreUser->id,
            'game_id' => $game->id,
            'achievements_unlocked' => 6,
            'achievements_unlocked_hardcore' => 0,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numCompletions', 1)
        );
    });

    it('includes num beaten counts', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $game->refresh();

        $beatenHardcoreUser = User::factory()->create();
        PlayerGame::factory()->create([
            'user_id' => $beatenHardcoreUser->id,
            'game_id' => $game->id,
            'beaten_at' => now(),
            'beaten_hardcore_at' => now(),
        ]);

        $beatenSoftcoreUser = User::factory()->create();
        PlayerGame::factory()->create([
            'user_id' => $beatenSoftcoreUser->id,
            'game_id' => $game->id,
            'beaten_at' => now(),
            'beaten_hardcore_at' => null,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numBeaten', 1)
            ->where('numBeatenSoftcore', 1)
        );
    });
});

describe('Aggregate Credits Props', function () {
    it('includes achievement authors in aggregate credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits')
            ->has('aggregateCredits.achievementsAuthors', 1)
        );
    });

    it('includes active maintainers who are not the original author', function () {
        // ARRANGE
        $system = System::factory()->create();
        $originalAuthor = User::factory()->create();
        $maintainer = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $originalAuthor->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        AchievementMaintainer::create([
            'achievement_id' => $achievement->id,
            'user_id' => $maintainer->id,
            'is_active' => true,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementsMaintainers', 1)
            ->where('aggregateCredits.achievementsMaintainers.0.displayName', $maintainer->display_name)
        );
    });

    it('excludes maintainers who are the original author', function () {
        // ARRANGE
        $system = System::factory()->create();
        $originalAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $originalAuthor->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        AchievementMaintainer::create([
            'achievement_id' => $achievement->id,
            'user_id' => $originalAuthor->id, // !!
            'is_active' => true,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementsMaintainers', 0)
        );
    });

    it('includes achievement author task credits by type', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $artworkAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        AchievementAuthor::create([
            'achievement_id' => $achievement->id,
            'user_id' => $artworkAuthor->id,
            'task' => AchievementAuthorTask::Artwork,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementsArtwork', 1)
            ->where('aggregateCredits.achievementsArtwork.0.displayName', $artworkAuthor->display_name)
        );
    });

    it('includes hash compatibility testing credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $hashUploader = User::factory()->create();
        $compatibilityTester = User::factory()->create();

        GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'user_id' => $hashUploader->id,
            'compatibility' => GameHashCompatibility::Compatible,
            'compatibility_tester_id' => $compatibilityTester->id,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.hashCompatibilityTesting', 1)
            ->where('aggregateCredits.hashCompatibilityTesting.0.displayName', $compatibilityTester->display_name)
        );
    });

    it('includes achievement set artwork credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $artworkAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();

        AchievementSetAuthor::create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'user_id' => $artworkAuthor->id,
            'task' => AchievementSetAuthorTask::Artwork,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementSetArtwork', 1)
            ->where('aggregateCredits.achievementSetArtwork.0.displayName', $artworkAuthor->display_name)
        );
    });

    it('includes achievement set banner credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $bannerAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();

        AchievementSetAuthor::create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'user_id' => $bannerAuthor->id,
            'task' => AchievementSetAuthorTask::Banner,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementSetBanner', 1)
            ->where('aggregateCredits.achievementSetBanner.0.displayName', $bannerAuthor->display_name)
        );
    });

    it('given multiple artwork authors for an achievement set, only credits the most recent', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $olderAuthor = User::factory()->create();
        $newerAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();

        AchievementSetAuthor::create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'user_id' => $olderAuthor->id,
            'task' => AchievementSetAuthorTask::Artwork,
            'created_at' => now()->subDays(10),
        ]);
        AchievementSetAuthor::create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'user_id' => $newerAuthor->id,
            'task' => AchievementSetAuthorTask::Artwork,
            'created_at' => now(),
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementSetArtwork', 1)
            ->where('aggregateCredits.achievementSetArtwork.0.displayName', $newerAuthor->display_name)
        );
    });

    it('given multiple banner authors for an achievement set, only credits the most recent', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $olderAuthor = User::factory()->create();
        $newerAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();

        AchievementSetAuthor::create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'user_id' => $olderAuthor->id,
            'task' => AchievementSetAuthorTask::Banner,
            'created_at' => now()->subDays(10),
        ]);
        AchievementSetAuthor::create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'user_id' => $newerAuthor->id,
            'task' => AchievementSetAuthorTask::Banner,
            'created_at' => now(),
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementSetBanner', 1)
            ->where('aggregateCredits.achievementSetBanner.0.displayName', $newerAuthor->display_name)
        );
    });

    it('excludes soft deleted users from maintainer credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $originalAuthor = User::factory()->create();
        $trashedMaintainer = User::factory()->create(['deleted_at' => now()]);
        $activeMaintainer = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $originalAuthor->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        AchievementMaintainer::create([
            'achievement_id' => $achievement->id,
            'user_id' => $trashedMaintainer->id,
            'is_active' => true,
        ]);
        AchievementMaintainer::create([
            'achievement_id' => $achievement->id,
            'user_id' => $activeMaintainer->id,
            'is_active' => true,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementsMaintainers', 1)
            ->where('aggregateCredits.achievementsMaintainers.0.displayName', $activeMaintainer->display_name)
        );
    });

    it('includes achievement design credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $taskAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        AchievementAuthor::create([
            'achievement_id' => $achievement->id,
            'user_id' => $taskAuthor->id,
            'task' => AchievementAuthorTask::Design,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementsDesign', 1)
            ->where('aggregateCredits.achievementsDesign.0.displayName', $taskAuthor->display_name)
        );
    });

    it('includes achievement logic credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $taskAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        AchievementAuthor::create([
            'achievement_id' => $achievement->id,
            'user_id' => $taskAuthor->id,
            'task' => AchievementAuthorTask::Logic,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementsLogic', 1)
            ->where('aggregateCredits.achievementsLogic.0.displayName', $taskAuthor->display_name)
        );
    });

    it('includes achievement testing credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $taskAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        AchievementAuthor::create([
            'achievement_id' => $achievement->id,
            'user_id' => $taskAuthor->id,
            'task' => AchievementAuthorTask::Testing,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementsTesting', 1)
            ->where('aggregateCredits.achievementsTesting.0.displayName', $taskAuthor->display_name)
        );
    });

    it('includes achievement writing credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $taskAuthor = User::factory()->create();

        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        AchievementAuthor::create([
            'achievement_id' => $achievement->id,
            'user_id' => $taskAuthor->id,
            'task' => AchievementAuthorTask::Writing,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.achievementsWriting', 1)
            ->where('aggregateCredits.achievementsWriting.0.displayName', $taskAuthor->display_name)
        );
    });

    it('given a compatible hash with no compatibility tester, does not include details for it in credits', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $hashUploader = User::factory()->create();

        GameHash::factory()->create([
            'game_id' => $game->id,
            'system_id' => $system->id,
            'user_id' => $hashUploader->id,
            'compatibility' => GameHashCompatibility::Compatible,
            'compatibility_tester_id' => null,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('aggregateCredits.hashCompatibilityTesting', 0)
        );
    });
});

describe('Achievement Groups Props', function () {
    it('includes achievement groups in game achievement sets', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement = Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();
        $group = AchievementGroup::factory()->create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'label' => 'Test Group',
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('game.gameAchievementSets.0.achievementSet.achievementGroups', 1)
            ->where('game.gameAchievementSets.0.achievementSet.achievementGroups.0.label', 'Test Group')
        );
    });

    it('given there is an empty achievement group, the page still loads without crashing', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        Achievement::factory()->promoted()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();

        AchievementGroup::factory()->create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'label' => 'Empty Group',
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertOk();
    });

    it('given a group with win condition achievements, the page loads correctly', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievement1 = Achievement::factory()->promoted()->winCondition()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'order_column' => 1,
        ]);

        $achievement2 = Achievement::factory()->promoted()->winCondition()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'order_column' => 2,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();

        $group = AchievementGroup::factory()->create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'label' => 'Win Conditions Group',
        ]);

        AchievementSetAchievement::query()
            ->where('achievement_set_id', $gameAchievementSet->achievement_set_id)
            ->whereIn('achievement_id', [$achievement1->id, $achievement2->id])
            ->update(['achievement_group_id' => $group->id]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertOk();
    });

    it('given a group with progression and missable achievements but no win conditions, the page still loads correctly', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $progressionAchievement1 = Achievement::factory()->promoted()->progression()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'order_column' => 1,
        ]);

        $progressionAchievement2 = Achievement::factory()->promoted()->progression()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'order_column' => 2,
        ]);

        $missableAchievement = Achievement::factory()->promoted()->missable()->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
            'order_column' => 3,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();

        $group = AchievementGroup::factory()->create([
            'achievement_set_id' => $gameAchievementSet->achievement_set_id,
            'label' => 'Progression Group',
        ]);

        AchievementSetAchievement::query()
            ->where('achievement_set_id', $gameAchievementSet->achievement_set_id)
            ->whereIn('achievement_id', [$progressionAchievement1->id, $progressionAchievement2->id, $missableAchievement->id])
            ->update(['achievement_group_id' => $group->id]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertOk();
    });
});

describe('Event Hub Props', function () {
    it('given a hub with an Events title, correctly sets the isEventHub flag', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ... the `is_event_hub` attribute is computed based on title containing "Events -" ...
        $eventHub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'title' => '[Events - Test Event]',
        ]);
        $eventHub->games()->attach([$game->id]);

        EventHubIdCacheService::clearCache();

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hubs', 1)
            ->where('hubs.0.isEventHub', true)
        );
    });

    it('given a non-event hub, does not set the isEventHub flag', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $regularHub = GameSet::factory()->create([
            'type' => GameSetType::Hub,
            'title' => '[Series - Test Series]', // !! not "[Events -"
        ]);
        $regularHub->games()->attach([$game->id]);

        EventHubIdCacheService::clearCache();

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('hubs', 1)
            ->missing('hubs.0.isEventHub')
        );
    });
});

describe('Subset Context Props', function () {
    it('given viewing a subset, claims come from the backing game', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame, 'subsetSet' => $subsetSet] = createGameWithSubset(
            $system,
            'Base Game',
            'Base Game [Subset - Bonus]'
        );

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ... create a claim on the backing game ("subset game"), not the base game ...
        AchievementSetClaim::factory()->create([
            'user_id' => $developer->id,
            'game_id' => $subsetGame->id,
            'claim_type' => ClaimType::Primary,
            'status' => ClaimStatus::Active,
        ]);

        // ACT
        $response = get(route('game.show', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id, // !!
        ]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('achievementSetClaims', 1)
        );
    });

    it('given viewing a subset, user game list state reflects the backing game', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame, 'subsetSet' => $subsetSet] = createGameWithSubset(
            $system,
            'Base Game',
            'Base Game [Subset - Bonus]'
        );
        $user = User::factory()->create();

        // ... add the "subset game" (backing game) to the user's Want to Play list, but not the base game ...
        UserGameListEntry::create([
            'user_id' => $user->id,
            'game_id' => $subsetGame->id,
            'type' => UserGameListType::Play,
        ]);

        // ACT
        $response = actingAs($user)->get(route('game.show', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id, // !!
        ]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isOnWantToPlayList', true)
        );
    });

    it('given viewing a subset, leaderboards come from the backing game', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetGame' => $subsetGame, 'subsetSet' => $subsetSet] = createGameWithSubset(
            $system,
            'Base Game',
            'Base Game [Subset - Bonus]'
        );

        // ... create leaderboards on the "subset game" (backing game) ...
        Leaderboard::factory()->count(3)->create([
            'game_id' => $subsetGame->id,
            'order_column' => 1,
        ]);

        // ACT
        $response = get(route('game.show', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id, // !!
        ]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numLeaderboards', 3)
        );
    });

    it('given a non-core set, player counts come from player_achievement_sets', function () {
        // ARRANGE
        $system = System::factory()->create();
        ['baseGame' => $baseGame, 'subsetSet' => $subsetSet] = createGameWithSubset(
            $system,
            'Base Game',
            'Base Game [Subset - Bonus]'
        );

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        PlayerAchievementSet::create([
            'user_id' => $user1->id,
            'achievement_set_id' => $subsetSet->achievement_set_id,
            'achievements_unlocked' => 3,
            'achievements_unlocked_hardcore' => 3,
        ]);
        PlayerAchievementSet::create([
            'user_id' => $user2->id,
            'achievement_set_id' => $subsetSet->achievement_set_id,
            'achievements_unlocked' => 2,
            'achievements_unlocked_hardcore' => 0, // !!
        ]);

        // ACT
        $response = get(route('game.show', [
            'game' => $baseGame,
            'set' => $subsetSet->achievement_set_id, // !!
        ]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('targetAchievementSetPlayersTotal', 2)
            ->where('targetAchievementSetPlayersHardcore', 1)
        );
    });
});

describe('Set Request Data Props', function () {
    it('given the user is a guest viewing a game with zero achievements, returns the right set request data', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id, 'achievements_published' => 0]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('setRequestData')
            ->where('setRequestData.hasUserRequestedSet', false)
            ->where('setRequestData.totalRequests', 0) // !!
            ->where('setRequestData.userRequestsRemaining', 0) // !!
        );
    });

    it('given the user is authenticated and viewing a game with zero achievements, returns set request data with the user request info', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id, 'achievements_published' => 0]);
        $user = User::factory()->create();

        // ACT
        $response = actingAs($user)->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('setRequestData')
            ->where('setRequestData.hasUserRequestedSet', false)
            ->where('setRequestData.userRequestsRemaining', fn ($value) => $value > 0)
        );
    });

    it('given a game with achievements, setRequestData is null', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('setRequestData')
        );
    });
});

describe('Cookie-Based Filter Props', function () {
    it('given hide_unlocked_achievements_games cookie contains the game id, isLockedOnlyFilterEnabled is true', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = $this->withUnencryptedCookies(['hide_unlocked_achievements_games' => (string) $game->id])
            ->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isLockedOnlyFilterEnabled', true)
        );
    });

    it('given hide_nonmissable_achievements_games cookie contains the game id, isMissableOnlyFilterEnabled is true', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        // ACT
        $response = $this->withUnencryptedCookies(['hide_nonmissable_achievements_games' => (string) $game->id])
            ->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isMissableOnlyFilterEnabled', true)
        );
    });

    it('given the cookie contains multiple game ids including the current game, returns true', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');
        $otherGame = createGameWithAchievements($system, 'Other Game');

        // ACT
        $response = $this->withUnencryptedCookies(['hide_unlocked_achievements_games' => "{$otherGame->id},{$game->id}"])
            ->get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isLockedOnlyFilterEnabled', true)
        );
    });
});

describe('Edge Cases Tests', function () {
    it('given an achievement set with null player counts, does not crash', function () {
        // ARRANGE
        $system = System::factory()->create();
        $game = createGameWithAchievements($system, 'Test Game');

        $gameAchievementSet = GameAchievementSet::where('game_id', $game->id)->first();
        $gameAchievementSet->achievementSet->update([
            'players_total' => null,
            'players_hardcore' => null,
        ]);

        // ACT
        $response = get(route('game.show', ['game' => $game]));

        // ASSERT
        $response->assertOk();
    });
});
