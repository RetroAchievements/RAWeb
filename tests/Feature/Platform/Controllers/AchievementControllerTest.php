<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\Role;
use App\Models\Subscription;
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
 * Creates a game with a single promoted achievement and a base achievement set.
 *
 * @return array{0: Game, 1: Achievement}
 */
function createGameWithAchievementAndSet(
    System $system,
    string $title = 'Test Game',
    array $achievementOverrides = [],
): array {
    $developer = User::factory()->create();

    $game = Game::factory()->create(['title' => $title, 'system_id' => $system->id]);
    $achievement = Achievement::factory()->promoted()->create(array_merge([
        'game_id' => $game->id,
        'user_id' => $developer->id,
    ], $achievementOverrides));

    (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

    return [$game, $achievement];
}

/**
 * Creates an event achievement with its event game and base achievement set.
 * Optionally links to a source achievement from a regular game.
 *
 * @return array{eventAchievement: Achievement, sourceAchievement: ?Achievement}
 */
function createEventAchievementWithSet(
    array $achievementOverrides = [],
    array $eventOverrides = [],
    bool $withSourceAchievement = true,
): array {
    System::factory()->create(['id' => System::Events]);
    $developer = User::factory()->create();

    $eventGame = Game::factory()->create(['system_id' => System::Events]);
    $eventAchievement = Achievement::factory()->promoted()->create(array_merge([
        'game_id' => $eventGame->id,
        'user_id' => $developer->id,
    ], $achievementOverrides));
    (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($eventGame);

    $sourceAchievement = null;
    if ($withSourceAchievement) {
        $regularSystem = System::factory()->create();
        $sourceGame = Game::factory()->create(['system_id' => $regularSystem->id]);
        $sourceAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $sourceGame->id,
            'user_id' => $developer->id,
        ]);
    }

    EventAchievement::create(array_merge([
        'achievement_id' => $eventAchievement->id,
        'source_achievement_id' => $sourceAchievement?->id,
        'active_from' => now()->subDay(),
        'active_until' => now()->addWeek(),
    ], $eventOverrides));

    return [
        'eventAchievement' => $eventAchievement,
        'sourceAchievement' => $sourceAchievement,
    ];
}

describe('Basic Rendering', function () {
    it('given the user is unauthenticated, renders the page', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system, 'Super Mario Bros.');

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('achievement/[achievement]')
            ->has('achievement')
            ->where('achievement.id', $achievement->id)
            ->where('achievement.title', $achievement->title)
        );
    });

    it('given the user is authenticated, renders the page', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system, 'Super Mario Bros.');
        $user = User::factory()->create();

        // ACT
        $response = actingAs($user)->get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('achievement/[achievement]')
            ->where('achievement.id', $achievement->id)
        );
    });

    it('includes core achievement data in props', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system, 'Super Mario Bros.');

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('achievement.description')
            ->has('achievement.points')
            ->has('achievement.badgeUnlockedUrl')
            ->has('achievement.badgeLockedUrl')
            ->has('achievement.game')
            ->has('achievement.game.system')
        );
    });

    it('given a tab query param, sets the initialTab prop', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement, 'tab' => 'changelog']));

        // ASSERT
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('initialTab', 'changelog')
        );
    });

    it('given no tab query param, defaults initialTab to comments', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('initialTab', 'comments')
        );
    });

});

describe('Permissions Props', function () {
    it('given the user is a guest, sets all permissions to false', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.createAchievementComments', false)
            ->where('can.develop', false)
            ->where('can.updateAchievementDescription', false)
            ->where('can.updateAchievementPoints', false)
            ->where('can.updateAchievementTitle', false)
            ->where('can.updateAchievementType', false)
            ->where('can.viewAchievementLogic', false)
        );
    });

    it('given an authenticated user with no roles, allows commenting only', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);
        $user = User::factory()->create(['points_hardcore' => 100]);

        // ACT
        $response = actingAs($user)->get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.createAchievementComments', true)
            ->where('can.develop', false)
            ->where('can.updateAchievementDescription', false)
            ->where('can.updateAchievementPoints', false)
            ->where('can.updateAchievementTitle', false)
            ->where('can.updateAchievementType', false)
            ->where('can.viewAchievementLogic', false)
        );
    });

    it('given the user is a developer, allows editing and viewing logic', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        // ACT
        $response = actingAs($developer)->get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('can')
            ->where('can.createAchievementComments', true)
            ->where('can.develop', true)
            ->where('can.updateAchievementDescription', true)
            ->where('can.updateAchievementIsPromoted', true)
            ->where('can.updateAchievementPoints', true)
            ->where('can.updateAchievementTitle', true)
            ->where('can.updateAchievementType', true)
            ->where('can.viewAchievementLogic', true)
        );
    });

    it('given the user is a junior developer without a claim, denies editing promoted achievements', function () {
        // ARRANGE
        seed(RolesTableSeeder::class);

        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        $juniorDev = User::factory()->create();
        $juniorDev->assignRole(Role::DEVELOPER_JUNIOR);

        // ACT
        $response = actingAs($juniorDev)->get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('can.updateAchievementDescription', false)
            ->where('can.updateAchievementPoints', false)
            ->where('can.updateAchievementTitle', false)
            ->where('can.updateAchievementType', false)
            ->where('can.viewAchievementLogic', true) // they can still view logic
        );
    });
});

describe('Comments Props', function () {
    it('includes the comment count', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        $user = User::factory()->create();
        Comment::factory()->count(3)->create([
            'commentable_id' => $achievement->id,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $user->id,
        ]);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('numComments', 3)
        );
    });

    it('includes recent visible comments', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        $user = User::factory()->create();
        Comment::factory()->count(5)->create([
            'commentable_id' => $achievement->id,
            'commentable_type' => CommentableType::Achievement,
            'user_id' => $user->id,
        ]);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('recentVisibleComments', 5)
        );
    });

    it('given the user is a guest, subscription state is always false', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isSubscribedToComments', false)
        );
    });

    it('given the user is authenticated and subscribed, subscription state is true', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);
        $user = User::factory()->create();

        Subscription::create([
            'subject_type' => SubscriptionSubjectType::Achievement,
            'subject_id' => $achievement->id,
            'user_id' => $user->id,
            'state' => true,
        ]);

        // ACT
        $response = actingAs($user)->get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isSubscribedToComments', true)
        );
    });
});

describe('Subset Context Props', function () {
    it('given an achievement from a non-subset game, backingGame and gameAchievementSet are null', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('backingGame')
            ->missing('gameAchievementSet')
        );
    });

    it('given an achievement from a subset game, includes backingGame and gameAchievementSet', function () {
        // ARRANGE
        $system = System::factory()->create();

        $baseGame = Game::factory()->create(['title' => 'Dragon Quest III', 'system_id' => $system->id]);
        Achievement::factory()->promoted()->count(6)->create([
            'game_id' => $baseGame->id,
            'user_id' => User::factory()->create()->id,
        ]);
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($baseGame);

        $subsetGame = Game::factory()->create(['title' => 'Dragon Quest III [Subset - Bonus]', 'system_id' => $system->id]);
        $subsetDeveloper = User::factory()->create();
        $subsetAchievement = Achievement::factory()->promoted()->create([
            'game_id' => $subsetGame->id,
            'user_id' => $subsetDeveloper->id,
        ]);
        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($subsetGame);

        (new AssociateAchievementSetToGameAction())->execute(
            $baseGame,
            $subsetGame,
            AchievementSetType::Bonus,
            'Bonus'
        );

        // ACT
        $response = get(route('achievement.show', ['achievement' => $subsetAchievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('backingGame')
            ->where('backingGame.id', $baseGame->id)
            ->has('gameAchievementSet')
        );
    });
});

describe('Proximity Achievements Props', function () {
    it('includes nearby achievements', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievements = Achievement::factory()->promoted()->count(8)->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $targetAchievement = $achievements->get(3);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $targetAchievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('proximityAchievements', 4)
        );
    });

    it('the current achievement is excluded from the proximity list', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievements = Achievement::factory()->promoted()->count(3)->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $targetAchievement = $achievements->first();

        // ACT
        $response = get(route('achievement.show', ['achievement' => $targetAchievement]));

        // ASSERT
        $response->assertInertia(function (Assert $page) use ($targetAchievement) {
            $page->has('proximityAchievements', 2)->etc();
            $proximityIds = collect($page->toArray()['props']['proximityAchievements'])->pluck('id');
            expect($proximityIds)->not->toContain($targetAchievement->id);
        });
    });

    it('includes the total promoted achievement count', function () {
        // ARRANGE
        $system = System::factory()->create();
        $developer = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $achievements = Achievement::factory()->promoted()->count(10)->create([
            'game_id' => $game->id,
            'user_id' => $developer->id,
        ]);

        (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);

        $achievement = $achievements->first();

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('promotedAchievementCount', 10)
        );
    });
});

describe('Changelog Props', function () {
    it('given a non-event achievement, includes the changelog', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->has('changelog')
        );
    });

    it('given an event achievement, the changelog is empty', function () {
        // ARRANGE
        ['eventAchievement' => $eventAchievement] = createEventAchievementWithSet();

        // ACT
        $response = get(route('achievement.show', ['achievement' => $eventAchievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('changelog', [])
        );
    });
});

describe('Event Achievement Props', function () {
    it('given an event achievement, sets isEventGame to true and includes eventAchievement data', function () {
        // ARRANGE
        ['eventAchievement' => $eventAchievement] = createEventAchievementWithSet();

        // ACT
        $response = get(route('achievement.show', ['achievement' => $eventAchievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isEventGame', true)
            ->has('eventAchievement')
        );
    });

    it('given a non-event achievement, eventAchievement is null', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('isEventGame', false)
            ->missing('eventAchievement')
        );
    });

    it('given an upcoming obfuscated event achievement, scrubs the title', function () {
        // ARRANGE
        ['eventAchievement' => $eventAchievement] = createEventAchievementWithSet(
            achievementOverrides: ['title' => 'Secret Challenge'],
            // ... active_from is in the future, so the achievement should be obfuscated ...
            eventOverrides: ['active_from' => now()->addDay()],
        );

        // ACT
        $response = get(route('achievement.show', ['achievement' => $eventAchievement]));

        // ASSERT
        $response->assertInertia(fn (Assert $page) => $page
            ->where('achievement.title', 'Upcoming Challenge')
        );
    });

    it('given an event game with a changelog tab query param, falls back to comments', function () {
        // ARRANGE
        ['eventAchievement' => $eventAchievement] = createEventAchievementWithSet(
            withSourceAchievement: false,
        );

        // ACT
        $response = get(route('achievement.show', [
            'achievement' => $eventAchievement,
            'tab' => 'changelog',
        ]));

        // ASSERT
        // ... event pages don't have a changelog tab, so it should fall back ...
        $response->assertInertia(fn (Assert $page) => $page
            ->where('initialTab', 'comments')
        );
    });
});

describe('Recent Unlocks Props', function () {
    it('given a non-event achievement, recentUnlocks is deferred', function () {
        // ARRANGE
        $system = System::factory()->create();
        [, $achievement] = createGameWithAchievementAndSet($system);

        $user = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
        ]);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $achievement]));

        // ASSERT
        // ... for non-event achievements, recentUnlocks is deferred and should not be in the initial payload ...
        $response->assertInertia(fn (Assert $page) => $page
            ->missing('recentUnlocks')
        );
    });

    it('given an event achievement, recentUnlocks is included in the initial payload', function () {
        // ARRANGE
        ['eventAchievement' => $eventAchievement] = createEventAchievementWithSet(
            withSourceAchievement: false,
        );

        $unlocker = User::factory()->create();
        PlayerAchievement::factory()->create([
            'user_id' => $unlocker->id,
            'achievement_id' => $eventAchievement->id,
        ]);

        // ACT
        $response = get(route('achievement.show', ['achievement' => $eventAchievement]));

        // ASSERT
        // ... for event achievements, recentUnlocks should be eagerly included ...
        $response->assertInertia(fn (Assert $page) => $page
            ->has('recentUnlocks', 1)
        );
    });
});
